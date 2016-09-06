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

}
