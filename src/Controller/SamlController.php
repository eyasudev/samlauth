<?php

namespace Drupal\samlauth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\samlauth\SamlAuth;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class \Drupal\samlauth\Controller\SamlController.
 */
class SamlController extends ControllerBase {

  /**
   * SAML authentication service.
   *
   * @var \Drupal\samlauth\SamlAuth
   */
  protected $samlAuth;

  /**
   * Constructor for Drupal\samlauth\Controller\SamlController.
   *
   * @param \Drupal\samlauth\Controller\SamlService $samlauth_saml
   *   An SAML authentication.
   */
  public function __construct(SamlAuth $saml_auth) {
    $this->samlAuth = $saml_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('samlauth.service')
    );
  }

  /**
   * Render service provider metadata XML.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function metadata() {
    return new Response(
      $this->samlAuth->getMetadata(), 200, ['Content-Type' => 'text/xml']
    );
  }

  /**
   * Process attribute consumer service response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An Symfony redirect response object.
   */
  public function acs() {
    return $this->samlAuth->processAcsResponse();
  }

  /**
   * Process single logout service response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An Symfony redirect response object.
   */
  public function sls() {
    return $this->samlAuth->processSlsResponse();
  }

  /**
   * Redirect to the SAML login service.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A redirect response object.
   */
  public function login() {
    return $this->samlAuth->login();
  }

  /**
   * Redirect to the SAML logout service.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A redirect response object.
   */
  public function logout() {
    return $this->samlAuth->logout();
  }
}
