<?php

namespace Drupal\samlauth;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\samlauth\Event\SamlAuthEvents;
use Drupal\samlauth\Event\SamlAuthProcessResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class \Drupal\samlauth\SamlAuthService.
 */
class SamlAuth implements SamlAuthInterface {

  /**
   * SAML authentication.
   *
   * @var \OneLogin_Saml2_Auth
   */
  protected $auth;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructor for \Drupal\samlauth\SamlAuthService.
   *
   * @param \Drupal\samlauth\SamlAuthSettings $settings
   *   The SAML authentication settings.
   */
  public function __construct(SamlAuthSettings $settings, EventDispatcherInterface $event_dispatcher) {
    $this->auth = new \OneLogin_Saml2_Auth($settings->formatted());
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function login($return_to = NULL) {
    return new TrustedRedirectResponse(
      $this->auth->login($return_to, [], FALSE, FALSE, TRUE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function logout($return_to = NULL) {
    return new TrustedRedirectResponse(
      $this->auth->logout($return_to, [], FALSE, FALSE, TRUE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processAcsResponse() {
    $this->auth->processResponse();

    $this->checkErrors();

    // Allow third-party modules to react on the SAML ACS response.
    $event = $this->eventDispatcher->dispatch(
      SamlAuthEvents::ACS_RESPONSE, new SamlAuthProcessResponse($this)
    );

    return new RedirectResponse($event->getRedirectUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function processSlsResponse() {
    $this->auth->processSLO();

    $this->checkErrors();

    // Allow third-party modules to react on the SAML SLO response.
    $event = $this->eventDispatcher->dispatch(
      SamlAuthEvents::SLS_RESPONSE, new SamlAuthProcessResponse($this)
    );

    return new RedirectResponse($event->getRedirectUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getNameId() {
    return $this->auth->getNameId();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata() {
    $settings = $this->getSettings();
    $metadata = $settings->getSPMetadata();

    if ($errors = $settings->validateMetadata($metadata)) {
      throw new \InvalidArgumentException(
        'Invalid SP metadata: ' . implode(', ', $errors),
        OneLogin_Saml2_Error::METADATA_SP_INVALID
      );

      return NULL;
    }

    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->auth->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    return $this->auth->getAttributes();
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->auth->isAuthenticated();
  }

  /**
   * Determine if errors exist.
   *
   * @return bool
   *   TRUE if errors exists; otherwise FALSE.
   */
  protected function hasErrors() {
    return !empty($this->auth->getErrors());
  }

  /**
   * Check if the SAML response had errors.
   *
   * @throws \Exception
   */
  protected function checkErrors() {
    if ($this->hasErrors()) {
      throw new \Exception(
        'Errors: ' . implode(', ', $this->auth->getErrors())
      );
    }
  }

}
