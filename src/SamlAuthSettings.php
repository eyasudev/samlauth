<?php

namespace Drupal\samlauth;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RequestContext;

/**
 * Class \Drupal\samlauth\SamlAuthSettings.
 */
class SamlAuthSettings {

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Config context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * Constructor for \Drupal\samlauth\SamlAuthSettings.
   *
   * @param ConfigFactoryInterface $config_factory
   *   A config factory object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestContext $context) {
    $this->context = $context;
    $this->config = $config_factory->get('samlauth.configuration');
  }

  /**
   * Get SAML raw settings.
   */
  public function raw() {
    return $this->config->get();
  }

  /**
   * Get SAML formatted settings.
   */
  public function formatted() {
    $config = $this->config;

    return [
      'sp' => [
        'entityId' => $this->getServiceProviderMetadataUrl(),
        'assertionConsumerService' => [
          'url' => $this->getAssertionConsumerServiceUrl(),
        ],
        'singleLogoutService' => array(
          'url' => $this->getSingleLogoutServiceUrl(),
        ),
        'NameIDFormat' => $config->get('providers.sp.name_id_format'),
        'x509cert' => \OneLogin_Saml2_Utils::formatCert($config->get('providers.sp.x509cert')),
        'privateKey' => \OneLogin_Saml2_Utils::formatPrivateKey($config->get('providers.sp.private_key')),
      ],
      'idp' => array(
        'entityId' => $config->get('providers.idp.entity_id'),
        'singleSignOnService' => array(
          'url' => $config->get('providers.idp.single_sign_on_service'),
        ),
        'singleLogoutService' => array(
          'url' => $config->get('providers.idp.single_log_out_service'),
        ),
        'x509cert' => \OneLogin_Saml2_Utils::formatCert($config->get('providers.idp.x509cert')),
      ),
      'security' => array(
        'wantNameId' => $config->get('advanced_settings.security.want_name_id'),
        'wantMessagesSigned' => $config->get('advanced_settings.security.want_messages_signed'),
        'authnRequestsSigned' => $config->get('advanced_settings.security.authn_requests_signed'),
        'requestedAuthnContext' => $config->get('advanced_settings.security.requested_authn_context'),
      ),
    ];
  }

  /**
   * Get SAML service provider metadata URL.
   *
   * @return string
   *   An absolute URL to the service provider metadata endpoint.
   */
  protected function getServiceProviderMetadataUrl() {
    return $this->context->getCompleteBaseUrl() . $this->config->get('providers.sp.entity_id');
  }

  /**
   * Get SAML single logout service URL.
   *
   * @return string
   *   An absolute URL to the single logout service endpoint.
   */
  protected function getSingleLogoutServiceUrl() {
    return \Drupal::urlGenerator()
      ->generateFromRoute('samlauth.saml_controller_sls', [], ['absolute' => TRUE]);
  }

  /**
   * Get SAML assertion consumer service URL.
   *
   * @return string
   *   An absolute URL to the assertion consumer service endpoint.
   */
  protected function getAssertionConsumerServiceUrl() {
    return \Drupal::urlGenerator()
      ->generateFromRoute('samlauth.saml_controller_acs', [], ['absolute' => TRUE]);
  }

}
