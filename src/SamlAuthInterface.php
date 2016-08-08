<?php

namespace Drupal\samlauth;

/**
 * Interface \Drupal\samlauth\SamlAuthInterface.
 */
interface SamlAuthInterface {

  /**
   * Initialize SAML SSO process.
   *
   * @param string $return_to
   *   A URL to redirect the user to.
   */
  public function login($return_to = NULL);

  /**
   * Initialize SAML SLO process.
   *
   * @param string $return_to
   *   A URL to redirect the user to.
   */
  public function logout($return_to = NULL);

  /**
   * Get SAML assertion name ID.
   *
   * @return string
   *   A unique name identifier.
   */
  public function getNameId();

  /**
   * Get SAML SP metadata.
   *
   * @return string
   *   An XML string of metadata.
   *
   * @throws \InvalidArgumentException
   */
  public function getMetadata();

  /**
   * Get SAML settings.
   *
   * @return \OneLogin_Saml2_Settings
   *   An SAML settings object.
   */
  public function getSettings();

  /**
   * Get SAML assertion attributes.
   *
   * @return array
   *   An array of the returned SAML attributes.
   */
  public function getAttributes();

  /**
   * Determine if SAML user is authenticated.
   *
   * @return bool
   *   TRUE if user is authenticated; otherwise FALSE.
   */
  public function isAuthenticated();

  /**
   * Process the SAML ACS Response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An Symfony response object.
   */
  public function processAcsResponse();

  /**
   * Process the SAML SLS Response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An Symfony response object.
   */
  public function processSlsResponse();

}
