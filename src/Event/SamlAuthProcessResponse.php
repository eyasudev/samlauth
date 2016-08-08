<?php

namespace Drupal\samlauth\Event;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\samlauth\SamlAuth;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class \Drupal\samlauth\Event\SamlAuthLoginEvent.
 */
class SamlAuthProcessResponse extends Event {

  /**
   * SAML authentication service.
   *
   * @var \Drupal\samlauth\SamlAuth
   */
  protected $samlAuth;

  /**
   * Redirect URL object.
   *
   * @var \Drupal\Core\Url
   */
  protected $redirectUrl;

  /**
   * Constructor for \Drupal\samlauth\Event\SamlAuthLoginEvent.
   */
  public function __construct(SamlAuth $saml_auth) {
    $this->samlAuth = $saml_auth;
  }

  /**
   * Get SAML authentication.
   */
  public function getSamlAuth() {
    return $this->samlAuth;
  }

  /**
   * Set redirect URL by URI.
   */
  public function setRedirectUrlFromUri($uri, $safe_uri = TRUE) {
    if ($safe_uri) {
      $uri = UrlHelper::stripDangerousProtocols($uri);
    }

    $this->redirectUrl = Url::fromUri($uri);
  }
  /**
   * Set redirect URL by route.
   *
   * @param string $route_name
   *   A Drupal route name.
   */
  public function setRedirectUrlFromRoute($route_name) {
    $this->redirectUrl = Url::fromRoute($route_name);
  }

  /**
   * Get redirect URL string.
   *
   * @return string
   *   A HTTP URL string.
   */
  public function getRedirectUrl() {
    return $this->redirectUrl->toString();
  }

  /**
   * Get HTTP request object.
   */
  public function getRequest() {
    return \Drupal::request();
  }

  /**
   * Get request "RelayState" parameter.
   *
   * @return string
   *   A URI of SAML relay state.
   */
  public function getRelayState() {
    return $this->getRequest()->get('RelayState');
  }

}
