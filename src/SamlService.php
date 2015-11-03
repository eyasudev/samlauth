<?php

/**
 * @file
 * Contains Drupal\samlauth\SamlService.
 */

namespace Drupal\samlauth;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use OneLogin_Saml2_Auth;
use InvalidArgumentException;

/**
 * Class SamlService.
 *
 * @package Drupal\samlauth
 */
class SamlService implements ContainerInjectionInterface {

  /**
   * @var \OneLogin_Saml2_Auth
   */
  protected $auth;

  /**
   * Constructor for Drupal\samlauth\SamlService.
   *
   * @param \OneLogin_Saml2_Auth $auth
   */
  public function __construct(OneLogin_Saml2_Auth $auth) {
    $this->auth = $auth;
  }

  /**
   * Factory method for dependency injection container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    $config = samlauth_get_config();
    $auth = new OneLogin_Saml2_Auth($config);
    return new static($auth);
  }

  /**
   * Show metadata about the local sp. Use this to configure your saml2 IDP
   *
   * @return mixed xml string representing metadata
   * @throws InvalidArgumentException
   */
  public function getMetadata() {
    $settings = $this->auth->getSettings();
    $metadata = $settings->getSPMetadata();
    $errors = $settings->validateMetadata($metadata);

    if (empty($errors)) {
      return $metadata;
    }
    else {
      throw new InvalidArgumentException(
        'Invalid SP metadata: ' . implode(', ', $errors),
        OneLogin_Saml2_Error::METADATA_SP_INVALID
      );
    }
  }

  /**
   * Initiate a SAML2 authentication flow.
   *
   * @param string $return_to
   *   The path to return the user to after a successful login.
   */
  public function login($return_to = null) {
    $this->auth->login($return_to);
  }

  /**
   * Initiate a SAML2 logout flow.
   *
   * @param null $return_to
   *   The path to return the user to after a successful login.
   */
  public function logout($return_to = null) {
    user_logout();
    $this->auth->logout($return_to, array('referrer' => $return_to));
  }

  /**
   * Process a SAML response (assertion consumer service)
   *
   * @return array|null
   *   Returns array with error description on error. Null otherwise.
   * @throws \OneLogin_Saml2_Error
   */
  public function acs() {
    $this->auth->processResponse();
    $errors = $this->auth->getErrors();

    if (!empty($errors)) {
      return $errors;
    }

    if (!$this->isAuthenticated()) {
      return array('error' => 'Could not authenticate.');
    }
  }

  // Helper function.
  public function getData() {
    return $this->auth->getAttributes();
  }

  /**
   * @return bool if a valid user was fetched from the saml assertion this request.
   */
  protected function isAuthenticated() {
    return $this->auth->isAuthenticated();
  }

}
