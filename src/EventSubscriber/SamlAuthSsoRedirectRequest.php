<?php

namespace Drupal\samlauth\EventSubscriber;

use Drupal\samlauth\SamlAuthInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class \Drupal\samlauth\EventSubscriber\SamlAuthSsoRedirectRequest.
 */
class SamlAuthSsoRedirectRequest implements EventSubscriberInterface {

  /**
   * SAML Authentication.
   *
   * @var \Drupal\samlauth\SamlAuthInterface
   */
  protected $samlAuth;

  /**
   * Constructor for \Drupal\samlauth\EventSubscriber\SamlAuthSsoRedirectRequest.
   *
   * @param SamlAuthInterface $saml_auth
   *   An SAML authentication object.
   */
  public function __construct(SamlAuthInterface $saml_auth) {
    $this->samlAuth = $saml_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'onHttpKernelRequest',
    ];
  }

  /**
   * React on the HTTP kernel request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   An HTTP kernel event object.
   */
  public function onHttpKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    if ($request->query->has('_checkSSO')) {
      if (TRUE === $request->query->getBoolean('_checkSSO')) {
        $event->setResponse($this->samlAuth->login()->send());
      }
    }
  }

}
