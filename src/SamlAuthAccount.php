<?php

namespace Drupal\samlauth;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\token\TokenInterface;
use Drupal\user\Entity\User;

/**
 * Class \Drupal\samlauth\SamlAuthAccount.
 */
class SamlAuthAccount implements SamlAuthAccountInterface {

  /**
   * Token object.
   *
   * @var \Drupal\token\TokenInterface
   */
  protected $token;

  /**
   * Account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * External authentication.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * External authentication map.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $externalAuthMap;

  /**
   * Constructor for \Drupal\samlauth\SamlAuthAccount.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   An account proxy object.
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   An external authentication object.
   */
  public function __construct(AccountProxyInterface $account_proxy, ExternalAuthInterface $external_auth, AuthmapInterface $external_authmap, ConfigFactoryInterface $config, TokenInterface $token) {
    $this->token = $token;
    $this->accountProxy = $account_proxy;
    $this->externalAuth = $external_auth;
    $this->externalAuthMap = $external_authmap;
    $this->userSettings = $config->get('samlauth.user.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function initialEmail() {
    return $this->getAccount()->init;
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    return (boolean) FALSE !== $this->loadExternalUser();
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return (boolean) $this->getAccount()->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    if (!$this->isAuthenticated()) {
      return NULL;
    }
    $token = $this->userSettings->get('account.username');

    $username = $this->token->replace(
      $token, $this->getTokenData(), ['clear' => TRUE]
    );

    return !empty($username) ? $username : $this->getAccount()->getDisplayName();
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthData() {
    $user = $this->loadExternalUser();

    if (!$user) {
      return [];
    }
    $records = $this->externalAuthMap->getAuthData($user->id(), 'samlauth');

    if (!isset($records['data'])) {
      return [];
    }

    return unserialize($records['data']);
  }

  /**
   * Get allowed token data.
   *
   * @return array
   *   An array of allowed token data.
   */
  protected function getTokenData() {
    $user = $this->loadUser();

    return [
      'user' => $user,
      'samlauth-account' => $this,
    ];
  }

  /**
   * Get user account object.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   An session account object.
   */
  protected function getAccount() {
    return $this->accountProxy->getAccount();
  }

  /**
   * Load account user object.
   *
   * @return \Drupal\user\UserInterface
   *   A user object.
   */
  protected function loadUser() {
    return User::load($this->accountProxy->id());
  }

  /**
   * Load external account user object.
   *
   * @return \Drupal\user\UserInterface|bool
   *   A user object; otherwise FALSE.
   */
  protected function loadExternalUser() {
    return $this->externalAuth->load($this->initialEmail(), 'samlauth');
  }

}
