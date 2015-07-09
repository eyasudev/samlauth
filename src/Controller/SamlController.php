<?php

/**
 * @file
 * Contains Drupal\samlauth\Controller\SamlController.
 */

namespace Drupal\samlauth\Controller;

use Exception;
use Drupal\samlauth\SamlService;
use Drupal\samlauth\SamlUserService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SamlController.
 *
 * @package Drupal\samlauth\Controller
 */
class SamlController extends ControllerBase {

  /**
   * @var Drupal\samlauth\SamlService
   */
  protected $saml;

  /**
   * @var Drupal\samlauth\SamlUserService
   */
  protected $saml_user;

  /**
   * Constructor for Drupal\samlauth\Controller\SamlController.
   *
   * @param \Drupal\samlauth\Controller\SamlService $samlauth_saml
   */
  public function __construct(SamlService $saml, SamlUserService $saml_user) {
    $this->saml = $saml;
    $this->saml_user = $saml_user;
  }

  /**
   * Factory method for dependency injection container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('samlauth.saml'),
      $container->get('samlauth.saml_user')
    );
  }

  /**
   * Redirect to the Login service on the IDP.
   */
  public function login() {
    $config = samlauth_get_config();
    $this->saml->login($config['sp']['assertionConsumerService']['url']);
  }

  /**
   * Redirect to the SLS service on the IDP.
   */
  public function logout() {
    $config = samlauth_get_config();
    $this->saml->logout($config['sp']['singleLogoutService']['url']);
  }

  /**
   * Displays service provider metadata XML for iDP autoconfiguration.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function metadata() {
    $metadata = $this->saml->getMetadata();
    $response = new Response($metadata, 200);
    $response->headers->set('Content-Type', 'text/xml');
    return $response;
  }

  /**
   * Attribute Consumer Service
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function acs() {
    $errors = $this->saml->acs();
    if (!empty($errors)) {
      drupal_set_message($this->t('An error occured.'), 'error');
      return new RedirectResponse('/');
    }

    try {
      $saml_data = $this->saml->getData();
      $this->saml_user->handleSamlData($saml_data);
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    $route = $this->saml_user->getPostLoginDestination();
    $url = \Drupal::urlGenerator()->generateFromRoute($route);
    return new RedirectResponse($url);
  }

  /**
   * Single Logout Service
   *
   * Return URL for the sls service on the identity provider.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function sls() {
    $this->saml_user->logout();

    $route = $this->saml_user->getPostLogoutDestination();
    $url = \Drupal::urlGenerator()->generateFromRoute($route);
    return new RedirectResponse($url);
  }

  /**
   * Change password redirector.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function changepw() {
    $url = \Drupal::config('samlauth.samlauthconfigure_config')->get('idp_change_password_service');
    return new RedirectResponse($url);
  }

}
