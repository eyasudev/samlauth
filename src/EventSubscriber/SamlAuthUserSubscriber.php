<?php

namespace Drupal\samlauth\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\samlauth\Event\SamlAuthEvents;
use Drupal\samlauth\Event\SamlAuthProcessResponse;
use Drupal\samlauth\SamlAuthInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class \Drupal\samlauth\EventSubscriber\SamlAuthUserSubscriber.
 */
class SamlAuthUserSubscriber implements EventSubscriberInterface {

  /**
   * External Authentication.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * SAML user mapping.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $userMapping;

  /**
   * SAML user settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $userSettings;

  /**
   * Constructor for \Drupal\samlauth\EventSubscriber\SamlAuthUserSubscriber.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   An external authentication service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   An configuration factory.
   */
  public function __construct(ExternalAuthInterface $external_auth, ConfigFactoryInterface $config) {
    $this->externalAuth = $external_auth;
    $this->userMapping = $config->get('samlauth.user.mapping');
    $this->userSettings = $config->get('samlauth.user.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SamlAuthEvents::ACS_RESPONSE => 'onProcessAcsResponse',
      SamlAuthEvents::SLS_RESPONSE => 'onProcessSlsResponse',
    ];
  }

  /**
   * React on the process ACS response.
   *
   * @param \Drupal\samlauth\Event\SamlAuthProcessResponse $event
   *   An event subscriber object.
   */
  public function onProcessAcsResponse(SamlAuthProcessResponse $event) {
    $saml_auth = $event->getSamlAuth();
    $user_data = $this->buildUserDataFromAttributes($saml_auth);

    // Login or register the SAML user into Drupal.
    $account = $this->externalAuth->loginRegister(
      $saml_auth->getNameId(), 'samlauth', $user_data, $saml_auth->getAttributes()
    );

    // Assign roles to the SAML Drupal user account.
    if ($assigned_role = $this->userMapping->get('user_roles.assigned_role')) {
      foreach (array_keys(array_filter($assigned_role)) as $role_id) {
        if ($account->hasRole($role_id)) {
          continue;
        }
        $account->addRole($role_id);
      }

      $account->save();
    }

    $this->setRedirectState($event, 'login');
  }

  /**
   * React on the process SLS response.
   *
   * @param \Drupal\samlauth\Event\SamlAuthProcessResponse $event
   *   An event subscriber object.
   */
  public function onProcessSlsResponse(SamlAuthProcessResponse $event) {
    user_logout();

    $this->setRedirectState($event, 'logout');
  }

  /**
   * Set redirect state for an event.
   *
   * @param \Drupal\samlauth\Event\SamlAuthProcessResponse $event
   *   An event subscriber object.
   * @param string $type
   *   The redirect state type, either login or logout.
   */
  protected function setRedirectState(SamlAuthProcessResponse $event, $type) {
    $relay_state_uri = $event->getRelayState();

    // Redirect user to the relay state URI. This can be overwritten from the
    // \Drupal\samlauth\SamlAuth::login() or \Drupal\samlauth\SamlAuth::logout()
    // method. If the relay state is referencing a login or logout URL then
    // redirect the user to the default route that is defined in the user
    // settings section.
    if (!preg_match("/https?:\/\/.+\/saml\/(?:login|logout)/", $relay_state_uri)) {
      $event->setRedirectUrlFromUri($relay_state_uri);
    }
    else {
      $event->setRedirectUrlFromRoute($this->userSettings->get("route.$type"));
    }

    return $this;
  }

  /**
   * Build user data based on SAML assertion attributes.
   *
   * @param \Drupal\samlauth\SamlAuthInterface $saml_auth
   *   A SAML authentication object.
   *
   * @return array
   *   An array of user data with the respected attribute data.
   */
  protected function buildUserDataFromAttributes(SamlAuthInterface $saml_auth) {
    $user_data = [];
    $attributes = $saml_auth->getAttributes();

    // Iterate over the user mapping values and build the user data array.
    foreach ($this->userMapping->get('user_mapping') as $field_name => $mapping) {
      if (!isset($mapping['attribute'])) {
        continue;
      }
      $attribute = $mapping['attribute'];

      if (!isset($attributes[$attribute]) || empty($attributes[$attribute])) {
        continue;
      }

      $user_data[$field_name] = $attributes[$attribute];
    }

    return $user_data;
  }

}
