<?php

namespace Drupal\samlauth;

/**
 * Interface \Drupal\samlauth\SamlAuthAccountInterface.
 */
interface SamlAuthAccountInterface {

  /**
   * User account identifier.
   *
   * @return int
   *   A unique user account id.
   */
  public function id();

  /**
   * Get external authentication name.
   *
   * @return string|bool
   *   An external authentication name; otherwise FALSE.
   */
  public function authname();

  /**
   * Get user account authentication data.
   *
   * @return array
   *   An array of account authentication data.
   */
  public function getAuthData();

  /**
   * User account username.
   *
   * @return string
   *   A SAML authentication user name.
   */
  public function getUsername();

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
