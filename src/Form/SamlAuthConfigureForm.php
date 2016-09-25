<?php

namespace Drupal\samlauth\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
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

    $form['providers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Providers'),
      '#prefix' => '<div id="samlauth-providers">',
      '#suffix' => '</div>',
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
    $entity_type_parents = ['providers', 'sp', 'entity_id_type'];

    $entity_id_type = $form_state->hasValue($entity_type_parents)
      ? $form_state->getValue($entity_type_parents)
      : $config->get(implode('.', $entity_type_parents), 'url');

    $form['providers']['sp']['entity_id_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity ID Type'),
      '#options' => $this->getEntityIdTypeOptions(),
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'wrapper' => 'samlauth-providers',
        'callback' => '::ajaxProviderCallback',
      ],
      '#default_value' => $entity_id_type,
    ];

    if (!is_null($entity_id_type)) {
      $form['providers']['sp']['entity_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Entity ID'),
        '#description' => $this->t('Specify a unique entity ID using a custom
          naming convention.'),
        '#default_value' => $config->get('providers.sp.entity_id'),
        '#required' => TRUE,
      ];

      if ($entity_id_type === 'url') {
        $form['providers']['sp']['entity_id'] += [
          '#size' => 25,
          '#field_prefix' => $GLOBALS['base_url'],
        ];

        $form['providers']['sp']['entity_id']['#default_value'] = $config->get(
          'providers.sp.entity_id',
          Url::fromRoute('samlauth.saml_controller_metadata')->toString()
        );

        $form['providers']['sp']['entity_id']['#description'] = $this->t('Specify
          a unique entity ID using a URL containing its own domain name.');
      }
    }

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
    $sp_parents = ['providers', 'sp'];

    if ($entity_id_type = $form_state->getValue(array_merge($sp_parents, ['entity_id_type']))) {

      // Validate the entity ID when the URL type has been selected.
      if ($entity_id_type === 'url') {
        $entity_id_parents = array_merge($sp_parents, ['entity_id']);
        $entity_id_value = $form_state->getValue($entity_id_parents);

        if (!UrlHelper::isValid($GLOBALS['base_url'] . $entity_id_value, TRUE)) {
          $element = NestedArray::getValue($form, $entity_id_parents);

          $form_state->setError(
            $element,
            $this->t('@title URL is invalid.', ['@title' => $element['#title']])
          );
        }
      }
    }
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
   * Ajax provider callback.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form elements to return.
   */
  public function ajaxProviderCallback(array $form, FormStateInterface $form_state) {
    return $form['providers'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'samlauth.configuration',
    ];
  }

  /**
   * Define entity id type options.
   *
   * @return array
   *   An array of entity id type options.
   */
  protected function getEntityIdTypeOptions() {
    return [
      'url' => $this->t('URL'),
      'custom' => $this->t('Custom'),
    ];
  }

}
