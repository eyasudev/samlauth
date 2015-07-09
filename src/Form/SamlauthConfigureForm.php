<?php

/**
 * @file
 * Contains Drupal\samlauth\Form\SamlauthConfigureForm.
 */

namespace Drupal\samlauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SamlauthConfigureForm.
 *
 * @package Drupal\samlauth\Form
 */
class SamlauthConfigureForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'samlauth.authentication'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'samlauth_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('samlauth.authentication');
    $config_array = samlauth_get_config();

    $form['saml_requirements'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('SAML Requirements'),
    );

    $form['saml_requirements']['drupal_saml_login'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow SAML users to login directly with Drupal'),
      '#description' => $this->t('If this option is enabled, users that have a remote SAML ID will also be allowed to login through the normal Drupal process (without the intervention of the configured identity provider). Note that if Drupal login is hidden, this option will have no effect.'),
      '#default_value' => $config->get('drupal_saml_login'),
    );

    $form['service_provider'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Service Provider Configuration'),
    );

    $form['service_provider']['config_info'] = array(
      '#theme' => 'item_list',
      '#items' => array(
        $this->t('Metadata URL') . ': ' . \Drupal::urlGenerator()->generateFromRoute('samlauth.saml_controller_metadata', array(), array('absolute' => TRUE)),
        $this->t('Assertion Consumer Service') . ': ' . $config_array['sp']['assertionConsumerService']['url'],
        $this->t('Single Logout Service') . ': ' . $config_array['sp']['singleLogoutService']['url'],
      ),
      '#empty' => array(),
      '#list_type' => 'ul',
    );

    $form['service_provider']['sp_entity_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#description' => $this->t('Specifies the identifier to be used to represent the SP.'),
      '#default_value' => $config->get('sp_entity_id'),
    );

    $form['service_provider']['sp_name_id_format'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name ID Format'),
      '#description' => $this->t('Specify the NameIDFormat attribute to request from the identity provider'),
      '#default_value' => $config->get('sp_name_id_format'),
    );

    $form['service_provider']['sp_x509_certificate'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('x509 Certificate'),
      '#description' => $this->t('Public x509 certificate of the SP. No line breaks or BEGIN CERTIFICATE or END CERTIFICATE lines.'),
      '#default_value' => $config->get('sp_x509_certificate'),
    );

    $form['service_provider']['sp_private_key'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Private Key'),
      '#description' => $this->t('Private key for SP. No line breaks or BEGIN CERTIFICATE or END CERTIFICATE lines.'),
      '#default_value' => $config->get('sp_private_key'),
    );

    $form['identity_provider'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Identity Provider Configuration'),
    );

    // @TODO: Allow a user to automagically populate this by providing a metadata URL for the iDP.
//    $form['identity_provider']['idp_metadata_url'] = array(
//      '#type' => 'url',
//      '#title' => $this->t('Metadata URL'),
//      '#description' => $this->t('URL of the XML metadata for the identity provider'),
//      '#default_value' => $config->get('idp_metadata_url'),
//    );

    $form['identity_provider']['idp_entity_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#description' => $this->t('Specifies the identifier to be used to represent the IDP.'),
      '#default_value' => $config->get('idp_entity_id'),
    );

    $form['identity_provider']['idp_single_sign_on_service'] = array(
      '#type' => 'url',
      '#title' => $this->t('Single Sign On Service'),
      '#description' => $this->t('URL where the SP will send the authentication request message.'),
      '#default_value' => $config->get('idp_single_sign_on_service'),
    );

    $form['identity_provider']['idp_single_log_out_service'] = array(
      '#type' => 'url',
      '#title' => $this->t('Single Log Out Service'),
      '#description' => $this->t('URL where the SP will send the logout request message.'),
      '#default_value' => $config->get('idp_single_log_out_service'),
    );

    $form['identity_provider']['idp_change_password_service'] = array(
      '#type' => 'url',
      '#title' => $this->t('Change Password Service'),
      '#description' => $this->t('URL where users will be redirected to change their password.'),
      '#default_value' => $config->get('idp_change_password_service'),
    );

    $form['identity_provider']['idp_x509_certificate'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('x509 Certificate'),
      '#description' => $this->t('Public x509 certificate of the IdP'),
      '#default_value' => $config->get('idp_x509_certificate'),
    );

    $form['user_info'] = array(
      '#title' => $this->t('User Info and Syncing'),
      '#type' => 'fieldset',
    );

    $form['user_info']['unique_id_attribute'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Unique identifier attribute'),
      '#description' => $this->t('Specify a SAML attribute that is always going to be unique on a per-user basis. This will be used to identify local users (and create new ones if the option is enabled.<br />Example: <em>eduPersonPrincipalName</em> or <em>eduPersonTargetedID</em>'),
      '#default_value' => $config->get('unique_id_attribute') ?: 'eduPersonTargetedID',
    );

    $form['user_info']['map_users'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Attempt to map SAML users to existing local users?'),
      '#description' => $this->t('If this option is enabled and a SAML authentication response is received for a user that already exists locally, and the user\'s email matches the configured attribute, the SAML user will be mapped to the local user and then logged in.'),
      '#default_value' => $config->get('map_users'),
    );

    $form['user_info']['map_users_email'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email attribute (for mapping)'),
      '#description' => $this->t('This attribute will be used for mapping SAML users to local Drupal users.'),
      '#default_value' => $config->get('map_users_email'),
      '#states' => array(
        'invisible' => array(
          ':input[name="map_users"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['user_info']['create_users'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create users specified by SAML server?'),
      '#description' => $this->t('If this option is enabled, users that do not exist in the Drupal database will be created if specified by a successful SAML authentication response.'),
      '#default_value' => $config->get('create_users'),
    );

    $form['user_info']['user_name_attribute'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User name attribute'),
      '#description' => $this->t('When SAML users are created, this field specifies which SAML attribute should be used for the Drupal user name.<br />Example: <em>cn</em> or <em>eduPersonPrincipalName</em>'),
      '#default_value' => $config->get('user_name_attribute') ?: 'cn',
      '#states' => array(
        'invisible' => array(
          ':input[name="create_users"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['user_info']['user_mail_attribute'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User email attribute'),
      '#description' => $this->t('When SAML users are created, this field specifies which SAML attribute should be used for the Drupal email address.<br />Example: <em>mail</em>'),
      '#default_value' => $config->get('user_mail_attribute') ?: 'email',
      '#states' => array(
        'invisible' => array(
          ':input[name="create_users"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['security'] = array(
      '#title' => $this->t('Security Options'),
      '#type' => 'fieldset',
    );

    $form['security']['security_authn_requests_sign'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Request signed authn requests'),
      '#default_value' => $config->get('security_authn_requests_sign'),
    );

    $form['security']['security_messages_sign'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Request messages to be signed'),
      '#default_value' => $config->get('security_messages_sign'),
    );

    $form['security']['security_name_id_sign'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Request signed NameID'),
      '#default_value' => $config->get('security_name_id_sign'),
    );

    $form['security']['security_request_authn_context'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Request authn context'),
      '#default_value' => $config->get('security_request_authn_context'),
    );

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
    parent::submitForm($form, $form_state);

    $this->config('samlauth.authentication')
      ->set('drupal_saml_login', $form_state->getValue('drupal_saml_login'))
      ->set('sp_entity_id', $form_state->getValue('sp_entity_id'))
      ->set('sp_name_id_format', $form_state->getValue('sp_name_id_format'))
      ->set('sp_x509_certificate', $form_state->getValue('sp_x509_certificate'))
      ->set('sp_private_key', $form_state->getValue('sp_private_key'))
      ->set('idp_entity_id', $form_state->getValue('idp_entity_id'))
      ->set('idp_single_sign_on_service', $form_state->getValue('idp_single_sign_on_service'))
      ->set('idp_single_log_out_service', $form_state->getValue('idp_single_log_out_service'))
      ->set('idp_change_password_service', $form_state->getValue('idp_change_password_service'))
      ->set('idp_x509_certificate', $form_state->getValue('idp_x509_certificate'))
      ->set('unique_id_attribute', $form_state->getValue('unique_id_attribute'))
      ->set('map_users', $form_state->getValue('map_users'))
      ->set('map_users_email', $form_state->getValue('map_users_email'))
      ->set('create_users', $form_state->getValue('create_users'))
      ->set('user_name_attribute', $form_state->getValue('user_name_attribute'))
      ->set('user_mail_attribute', $form_state->getValue('user_mail_attribute'))
      ->set('security_authn_requests_sign', $form_state->getValue('security_authn_requests_sign'))
      ->set('security_messages_sign', $form_state->getValue('security_messages_signe'))
      ->set('security_name_id_sign', $form_state->getValue('security_name_id_sign'))
      ->set('security_request_authn_context', $form_state->getValue('security_request_authn_context'))
      ->save();
  }
}
