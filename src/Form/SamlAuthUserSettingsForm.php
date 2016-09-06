<?php

namespace Drupal\samlauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class \Drupal\samlauth\Form\SamlAuthUserSettingsForm.
 */
class SamlAuthUserSettingsForm extends ConfigFormBase {

  /**
   * Route provider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Constructor for \Drupal\samlauth\Form\SamlAuthUserSettingsForm.
   */
  public function __construct(RouteProvider $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'samlauth_user_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'samlauth.user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('samlauth.user.settings');

    $form['user_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User Mapping'),
      '#tree' => TRUE,
    ];
    $form['user_mapping']['attributes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('IDP Attributes'),
      '#description' => $this->t('Input available assertion attributes defined
        by the IDP. <br/> <strong>Note:</strong> Only one IDP attribute
        per line.'),
      '#default_value' => $config->get('user_mapping.attributes'),
    ];
    $form['route'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Destination Route'),
      '#tree' => TRUE,
    ];
    $form['route']['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login'),
      '#description' => $this->t('Input the route name which the user will be
        redirected to after login.'),
      '#default_value' => $config->get('route.login'),
      '#required' => TRUE,
    ];
    $form['route']['logout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logout'),
      '#description' => $this->t('Input the route name which the user will be
        redirected to after logout.'),
      '#default_value' => $config->get('route.logout'),
      '#required' => TRUE,
    ];
    $form['account'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Account'),
      '#tree' => TRUE,
    ];
    $form['account']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Override the account username that is
        displayed using tokens.'),
      '#default_value' => $config->get('account.username'),
      '#required' => TRUE,
    ];

    // Add support for the token UI module if it's enabled.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['account']['username']['#token_types'] = $this->allowedTokenTypes();
      $form['account']['username']['#element_validate'] = ['token_element_validate'];

      $form['account']['token_replacements'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $this->allowedTokenTypes(),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $attributes = $form_state->getValue(['user_mapping', 'attributes']);

    // Check if the user mapping attributes are in use.
    if ($in_use_attributes = $this->attributesAreInUse($attributes)) {

      foreach ($in_use_attributes as $attribute_name => $mapping_keys) {
        $form_state->setErrorByName('user_mapping][attributes',
          $this->t('The "@attribute_name" attribute is in use by: @mapping_keys.
            Remove all associations before removing the attribute.', [
              '@attribute_name' => $attribute_name,
              '@mapping_keys' => implode(', ', $mapping_keys),
            ]
          )
        );
      }
    }

    // Check if the route name exist in the Drupal routing.
    foreach ($form_state->getValue('route') as $name => $route_name) {
      if (!isset($route_name)) {
        continue;
      }

      if (!$this->routeNameExist($route_name)) {
        $form_state->setErrorByName("route][$name", $this->t(
          'The "@route_name" route does not exist.', ['@route_name' => $route_name]
        ));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('samlauth.user.settings')
      ->setData($form_state->cleanValues()->getValues())
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Define the allowed token replacement types.
   *
   * @return array
   *   An array of allowed token types.
   */
  protected function allowedTokenTypes() {
    return ['samlauth-account', 'user'];
  }

  /**
   * Route name exist.
   *
   * @param string $route_name
   *   A Drupal route name.
   *
   * @return bool
   *   TRUE if the route name exist; otherwise FALSE.
   */
  protected function routeNameExist($route_name) {
    return !empty($this->routeProvider->getRoutesByNames([$route_name]));
  }

  /**
   * Attributes that are in use by the user mapping.
   *
   * @param array $attributes
   *   An array of attributes to verify.
   *
   * @return array
   *   An array of attributes that are in use.
   */
  protected function attributesAreInUse($attributes) {
    $original_attributes = $this
      ->config('samlauth.user.settings')
      ->get('user_mapping.attributes');

    $removed_attributes = array_diff(
      explode("\r\n", $original_attributes), explode("\r\n", $attributes)
    );
    $attribute_in_use = [];

    foreach ($this->config('samlauth.user.mapping')->get('user_mapping') as $field_name => $mapping) {
      if (!isset($mapping['attribute'])) {
        continue;
      }
      $attribute = $mapping['attribute'];

      if (in_array($attribute, $removed_attributes)) {
        $attribute_in_use[$attribute][] = $field_name;
      }
    }

    return $attribute_in_use;
  }

}
