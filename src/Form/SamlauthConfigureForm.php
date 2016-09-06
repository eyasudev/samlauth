<?php

namespace Drupal\samlauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class \Drupal\samlauth\Form\SamlAuthConfigureForm.
 */
class SamlAuthConfigureForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'samlauth_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('samlauth.configuration');
    $metadata_uri = Url::fromRoute('samlauth.saml_controller_metadata')->toString();

    $form['providers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Providers'),
      '#tree' => TRUE,
    ];

    // SAML service provider.
    $form['providers']['sp'] = [
      '#type' => 'details',
      '#title' => $this->t('Service (SP)'),
      '#description' => $this->t('Input the configurations needed for the SAML
        service provider.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['providers']['sp']['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#default_value' => $config->get('providers.sp.entity_id', $metadata_uri),
      '#required' => TRUE,
      '#size' => 25,
      '#field_prefix' => $GLOBALS['base_url'],
    ];
    $form['providers']['sp']['name_id_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name ID Format'),
      '#description' => $this->t('Specify a NameIDFormat attribute to request from the IDP.'),
      '#default_value' => $config->get('providers.sp.name_id_format'),
    ];
    $form['providers']['sp']['x509cert'] = [
      '#type' => 'textarea',
      '#title' => $this->t('x509 Certificate'),
      '#default_value' => $config->get('providers.sp.x509cert'),
    ];
    $form['providers']['sp']['private_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Private Key'),
      '#default_value' => $config->get('providers.sp.private_key'),
    ];

    // SAML identify provider.
    $form['providers']['idp'] = [
      '#type' => 'details',
      '#title' => $this->t('Identity (IDP)'),
      '#description' => $this->t('Input the configurations needed for the SAML
        identify provider.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['providers']['idp']['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#description' => $this->t('Input the IDP metadata URL or a custom entity id.'),
      '#default_value' => $config->get('providers.idp.entity_id'),
      '#required' => TRUE,
    ];
    $form['providers']['idp']['single_sign_on_service'] = [
      '#type' => 'url',
      '#title' => $this->t('Single Sign On Service'),
      '#description' => $this->t('A endpoint where the SP will send the SSO request.'),
      '#default_value' => $config->get('providers.idp.single_sign_on_service'),
      '#required' => TRUE,
    ];
    $form['providers']['idp']['single_log_out_service'] = [
      '#type' => 'url',
      '#title' => $this->t('Single Log Out Service'),
      '#description' => $this->t('A endpoint where the SP will send the SLO request.'),
      '#default_value' => $config->get('providers.idp.single_log_out_service'),
      '#required' => TRUE,
    ];
    $form['providers']['idp']['x509cert'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('x509 Certificate'),
      '#default_value' => $config->get('providers.idp.x509cert'),
    );

    // Advanced settings.
    $form['advanced_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced Settings'),
      '#tree' => TRUE,
    ];
    $form['advanced_settings']['security'] = [
      '#type' => 'details',
      '#title' => $this->t('Security'),
      '#open' => FALSE,
    ];
    $form['advanced_settings']['security']['authn_requests_signed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Request signed authn requests'),
      '#default_value' => $config->get('advanced_settings.security.authn_requests_signed'),
    ];
    $form['advanced_settings']['security']['want_messages_signed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Request messages to be signed'),
      '#default_value' => $config->get('advanced_settings.security.want_messages_signed'),
    ];
    $form['advanced_settings']['security']['want_name_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Request signed NameID'),
      '#default_value' => $config->get('advanced_settings.security.want_name_id'),
    ];
    $form['advanced_settings']['security']['requested_authn_context'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Request authn context'),
      '#default_value' => $config->get('advanced_settings.security.requested_authn_context'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // @TODO: Validate cert. Might be able to just openssl_x509_parse().
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('samlauth.configuration')
      ->setData($form_state->cleanValues()->getValues())
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'samlauth.configuration',
    ];
  }

}
