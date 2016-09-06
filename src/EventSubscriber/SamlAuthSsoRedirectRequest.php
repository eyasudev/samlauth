<?php

namespace Drupal\samlauth\EventSubscriber;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class \Drupal\samlauth\EventSubscriber\SamlAuthSsoRedirectRequest.
 */
class SamlAuthSsoRedirectRequest implements EventSubscriberInterface {

  use ContainerAwareTrait;

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
        $event->setResponse(
          $this->container->get('samlauth.service')->login()->send()
        );
      }
    }
  }

}
