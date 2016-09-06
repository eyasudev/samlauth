<?php

/**
 * @file
 * Define the token implementations for the samlauth module.
 */
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\samlauth\SamlAuthAccountInterface;

/**
 * Implements hook_token_info().
 */
function samlauth_token_info() {
  $types['samlauth-account'] = [
    'name' => t('SAMLAuth Account'),
    'description' => t('Tokens related to SAML authentication account.'),
    'needs-data' => 'samlauth-account',
  ];
  $tokens = [];

  $tokens['attribute'] = [
    'name' => t('Attribute'),
    'description' => t('SAML authentication account attribute.'),
    'dynamic' => TRUE,
  ];

  return [
    'types' => $types,
    'tokens' => [
      'samlauth-account' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function samlauth_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'samlauth-account' && isset($data['samlauth-account'])) {
    $saml_account = $data['samlauth-account'];

    if ($saml_account instanceof SamlAuthAccountInterface) {
      $token = \Drupal::token();
      foreach ($tokens as $name => $original) {

        if ($attribute = $token->findWithPrefix([$name => $original], 'attribute')) {
          $attribute = key($attribute);
          $auth_data = $saml_account->getAuthData();

          if (isset($auth_data[$attribute])) {
            $replacements[$original] = reset($auth_data[$attribute]);
          }
        }
      }
    }
  }

  return $replacements;
}
