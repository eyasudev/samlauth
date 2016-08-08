<?php

namespace Drupal\samlauth\Event;

/**
 * Class \Drupal\samlauth\Event\SamlAuthEvents.
 */
final class SamlAuthEvents {

  /**
   * Name of the event fired after processing the SAML ACS response.
   *
   * This event allows modules to set a custom response object, along with
   * reacting on data that was received from the response. The event listener
   * method receives a \Drupal\samlauth\Event\SamlAuthProcessResponse instance.
   *
   * @Event
   *
   * @see \Drupal\samlauth\Event\SamlAuthProcessResponse
   *
   * @var string
   */
  const ACS_RESPONSE = 'samlauth.acs_response';

  /**
   * Name of the event fired after processing the SAML SLS response.
   *
   * This event allows modules to set a custom response object, along with
   * reacting on data that was received from the response. The event listener
   * method receives a \Drupal\samlauth\Event\SamlAuthProcessResponse instance.
   *
   * @Event
   *
   * @see \Drupal\samlauth\Event\SamlAuthProcessResponse
   *
   * @var string
   */
  const SLS_RESPONSE = 'samlauth.sls_response';

}
