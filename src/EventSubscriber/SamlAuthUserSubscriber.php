<?php

namespace Drupal\samlauth\EventSubscriber;

use Drupal\samlauth\Event\SamlAuthEvents;
use Drupal\samlauth\Event\SamlAuthProcessResponse;
use Drupal\samlauth\SamlAuthAccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class \Drupal\samlauth\EventSubscriber\SamlAuthUserSubscriber.
 */
class SamlAuthUserSubscriber implements EventSubscriberInterface {

  /**
   * SAML authentication account.
   *
   * @var \Drupal\samlauth\SamlAuthAccountInterface
   */
  protected $samlAuthAccount;

  /**
   * Constructor for \Drupal\samlauth\EventSubscriber\SamlAuthUserSubscriber.
   *
   * @param \Drupal\samlauth\SamlAuthAccountInterface $saml_auth_account
   *   An SAML authentication account object.
   */
  public function __construct(SamlAuthAccountInterface $saml_auth_account) {
    $this->samlAuthAccount = $saml_auth_account;
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
    $auth = $event->getSamlAuth();

    $this->samlAuthAccount
      ->loginRegister($auth->getNameId(), $auth->getAttributes())
      ->setSessionIndex($auth->getSessionIndex())
      ->setSessionExpiration($auth->getSessionExpiration());

    $this->setRedirectState($event, 'login');
  }

  /**
   * React on the process SLS response.
   *
   * @param \Drupal\samlauth\Event\SamlAuthProcessResponse $event
   *   An event subscriber object.
   */
  public function onProcessSlsResponse(SamlAuthProcessResponse $event) {
    $this->samlAuthAccount->logout();

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
      $event->setRedirectUrlFromRoute($this->samlAuthAccount->redirectRoute($type));
    }

    return $this;
  }

}
