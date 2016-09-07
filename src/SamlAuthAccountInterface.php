<?php

namespace Drupal\samlauth;

/**
 * Interface \Drupal\samlauth\SamlAuthAccountInterface.
 */
interface SamlAuthAccountInterface {

  /**
   * Check if the user account is external.
   *
   * @return bool
   *   TRUE if the user registered using SAML; otherwise FALSE.
   */
  public function isExternal();

  /**
   * Check if the user account is authenticated.
   *
   * @return bool
   *   TRUE if the user is authenticated; otherwise FALSE.
   */
  public function isAuthenticated();

  /**
   * User account initial email address.
   *
   * @return string
   *   A string representing a unique account identifier, most commonly this is
   *   an email address a user initially registered.
   */
  public function initialEmail();

  /**
   * User account username.
   *
   * @return string
   *   A SAML authentication user name.
   */
  public function getUsername();

  /**
   * Get user account authentication data.
   *
   * @return array
   *   An array of account authentication data.
   */
  public function getAuthData();

  /**
   * Logout external authenticated user from Drupal.
   */
  public function logout();

  /**
   * Login and/or register external authenticated user with Drupal.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param array $attributes
   *   An array of SAML attributes that were returned from the IdP.
   *
   * @return self
   */
  public function loginRegister($authname, array $attributes = []);

  /**
   * Get redirect route name based on type.
   *
   * @param string $type
   *   The redirect type, either login or logout.
   *
   * @return string
   *   A route name based on given type.
   */
  public function redirectRoute($type);

}
