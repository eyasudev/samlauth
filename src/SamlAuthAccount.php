<?php

namespace Drupal\samlauth;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\samlauth\Exception\SamlAuthAccountStorageExecption;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Class \Drupal\samlauth\SamlAuthAccount.
 */
class SamlAuthAccount implements SamlAuthAccountInterface {

  /**
   * Token object.
   *
   * @var \Drupal\Core\Utility\Token
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
   * Private account session store.
   *
   * @var \Drupal\user\PrivateTempStore.
   */
  protected $privateTempStore;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for \Drupal\samlauth\SamlAuthAccount.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   A account proxy object.
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   A external authentication object.
   * @param \Drupal\externalauth\AuthmapInterface $external_authmap
   *   A external authmap object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A configuration factory.
   * @param \Drupal\Core\Utility\Token $token
   *   A token object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   A entity type manager object.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   A temp data store factory object.
   */
  public function __construct(
    AccountProxyInterface $account_proxy,
    ExternalAuthInterface $external_auth,
    AuthmapInterface $external_authmap,
    ConfigFactoryInterface $config,
    Token $token,
    EntityTypeManagerInterface $entity_type_manager,
    PrivateTempStoreFactory $temp_store_factory) {

    $this->token = $token;
    $this->accountProxy = $account_proxy;
    $this->externalAuth = $external_auth;
    $this->externalAuthMap = $external_authmap;
    $this->entityTypeManager = $entity_type_manager;
    $this->userMapping = $config->get('samlauth.user.mapping');
    $this->userSettings = $config->get('samlauth.user.settings');
    $this->privateTempStore = $temp_store_factory->get('samlauth');
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->accountProxy->id();
  }

  /**
   * Get external authentication name.
   *
   * @return string|bool
   *   An external authentication name; otherwise FALSE.
   */
  public function authname() {
    return $this
      ->externalAuthMap
      ->get($this->id(), 'samlauth');
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthData() {
    $records = $this
      ->externalAuthMap
      ->getAuthData($this->id(), 'samlauth');

    if (empty($records['data'])) {
      return [];
    }
    $data = unserialize($records['data']);

    if (FALSE === $data || !is_array($data)) {
      return [];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    if (!$this->isAuthenticated()) {
      return NULL;
    }
    $data = $this->getTokenData();
    $token = $this->userSettings->get('account.username');
    $username = $this->token->replace($token, $data, ['clear' => TRUE]);

    return $username ?: $this->getAccount()->getDisplayName();
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    return (boolean) FALSE !== $this->authname();
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
  public function logout() {
    $this->deleteSessionData();
    user_logout();
  }

  /**
   * {@inheritdoc}
   */
  public function loginRegister($authname, array $attributes = []) {
    $account_data = $this
      ->linkAccountByAttributes($authname, $attributes)
      ->buildAccountDataByAttributes($attributes);

    try {
      $this->externalAuth->loginRegister(
        $authname, 'samlauth', $account_data, $attributes
      );
    }
    catch (EntityStorageException $e) {
      throw new SamlAuthAccountStorageExecption($e->getCode());
    }

    $this
      ->assignAccountRoles()
      ->refreshAuthAttributes($attributes);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function redirectRoute($type) {
    if ($type !== 'login' || $type !== 'logout') {
      return '<front>';
    }

    return $this->userSettings->get("route.$type");
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionIndex() {
    $session_index = $this->privateTempStore->get('session_index');

    return isset($session_index) ? $session_index : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionExpiration() {
    $session_expiration = $this->privateTempStore->get('session_expiration');

    return isset($session_expiration) ? $session_expiration : NULL;
  }

  /**
   * Set account session index.
   *
   * @param string $index
   *   The SAML session index.
   */
  public function setSessionIndex($index) {
    if (isset($index)) {
      $this->privateTempStore->set('session_index', $index);
    }

    return $this;
  }

  /**
   * Set account session expiration.
   *
   * @param string $expiration
   *   The SAML session expiration date.
   */
  public function setSessionExpiration($expiration) {
    if (isset($expiration)) {
      $this->privateTempStore->set('session_expiration', $expiration);
    }

    return $this;
  }

  /**
   * Delete private stored session information.
   */
  protected function deleteSessionData() {
    $keys = ['session_index', 'session_expiration'];

    foreach ($keys as $key) {
      $this->privateTempStore->delete($key);
    }

    return $this;
  }

  /**
   * Get token data payload.
   *
   * @return array
   *   An array of allowed token data.
   */
  protected function getTokenData() {
    return [
      'user' => $this->loadUser(),
      'samlauth-account' => $this,
    ];
  }

  /**
   * Get user account object.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   A user account session object.
   */
  protected function getAccount() {
    return $this->accountProxy->getAccount();
  }

  /**
   * Load user account object.
   *
   * @return \Drupal\user\UserInterface
   *   A user account object.
   */
  protected function loadUser() {
    return $this->entityTypeManager->getStorage('user')->load($this->id());
  }

  /**
   * Query user account object.
   *
   * @param string $conjunction
   *   (optional) The logical operator for the query, either:
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query object.
   */
  protected function queryUser($conjunction) {
    return $this->entityTypeManager->getStorage('user')->getQuery($conjunction);
  }

  /**
   * Assign account configured roles to current account.
   *
   * @param bool $save_account
   *   TRUE if you would like to save roles to account; Otherwise FALSE.
   *
   * @return self
   */
  protected function assignAccountRoles($save_account = TRUE) {
    $account = $this->loadUser();

    if ($assigned_role = $this->userMapping->get('user_roles.assigned_role')) {
      foreach (array_keys(array_filter($assigned_role)) as $role_id) {
        if ($account->hasRole($role_id)) {
          continue;
        }
        $account->addRole($role_id);
      }

      if ($save_account) {
        $account->save();
      }
    }

    return $this;
  }

  /**
   * Refresh external authentication SAML assertion attributes.
   *
   * @param array $attributes
   *   An array of the newest assertion attributes.
   *
   * @return self
   */
  protected function refreshAuthAttributes(array $attributes = []) {
    $original = $this->getAuthData();

    if ($changed = $this->compareArrays($original, $attributes)) {
      $this->saveAuthMapData(array_merge($original, $changed));
    }

    return $this;
  }

  /**
   * Determine if an authentication name exists.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   *
   * @return bool
   *   TRUE if authentication name exists; otherwise FALSE.
   */
  protected function hasauthname($authname) {
    return (boolean) $this->externalAuthMap->get($authname, 'samlauth');
  }

  /**
   * Link user account by SAML assertion attributes.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param array $attributes
   *   An array of SAML assertion attributes.
   *
   * @return self
   */
  protected function linkAccountByAttributes($authname, array $attributes = []) {
    if (!$this->hasauthname($authname)) {
      if ($account = $this->loadAccountByAttributes($attributes)) {
        $this->externalAuth->linkExistingAccount($authname, 'samlauth', $account);
      }
    }

    return $this;
  }

  /**
   * Load user account by SAML assertion attributes.
   *
   * This method tries its best to find a Drupal user account based on the SAML
   * assertion attributes. If more then one account is found then no account
   * will be linked, instead FALSE will be returned.
   *
   * @param array $attributes
   *   An array of SAML assertion attributes.
   *
   * @return \Drupal\user\UserInterface|bool
   *   A Drupal user object; otherwise FALSE.
   */
  protected function loadAccountByAttributes(array $attributes) {
    if (empty($attributes)) {
      return FALSE;
    }
    $conjunction = $this->userSettings->get('account.linking.conjunction');

    // Add conditions to the user query only if the property is defined to use
    // account linking.
    $user_query = $this->queryUser($conjunction);

    foreach ($this->userMapping() as $field_name => $mapping) {
      if (!isset($attributes[$mapping['attribute']])
        || !$mapping['settings']['use_account_linking']) {
        continue;
      }

      $user_query
        ->condition($field_name, $attributes[$mapping['attribute']]);
    }
    $results = $user_query->execute();

    if (count($results) !== 1) {
      return FALSE;
    }
    $user_id = reset($results);

    return User::load($user_id);
  }

  /**
   * Build user account data by SAML assertion attributes.
   *
   * @param array $attributes
   *   An array of SAML assertion attributes.
   *
   * @return array
   *   An array of user data with the respected attribute data.
   */
  protected function buildAccountDataByAttributes(array $attributes) {
    if (empty($attributes)) {
      return [];
    }
    $account_data = [];

    foreach ($this->userMapping() as $field_name => $mapping) {
      if (!isset($attributes[$mapping['attribute']])
        || empty($attributes[$mapping['attribute']])) {
        continue;
      }
      $account_data[$field_name] = $attributes[$mapping['attribute']];
    }

    return $account_data;
  }

  /**
   * Retrieve available user mappings that have attributes referenced.
   *
   * @return array
   *   An array of user mappings.
   */
  protected function userMapping() {
    $user_mapping = [];

    if ($user_mappings = $this->userMapping->get('user_mapping')) {
      foreach ($user_mappings as $field_name => $mapping) {
        if (empty($mapping['attribute'])) {
          continue;
        }

        $user_mapping[$field_name] = $mapping;
      }
    }

    return $user_mapping;
  }

  /**
   * Compare arrays with item values as array|string.
   *
   * @param array $original
   *   An array of original item values.
   * @param array $updated
   *   An array of updated item values.
   *
   * @return array
   *   An array of the differences.
   */
  protected function compareArrays(array $original, array $updated = []) {
    return @array_udiff($updated, $original,
      function ($a, $b) {

        if (is_array($a) && is_array($b)) {
          return (string) $a[0] !== (string) $b[0];
        }

        return (string) $a !== (string) $b;
      }
    );
  }

  /**
   * Save authentication map data.
   *
   * @param array $authmap_data
   *   An array of authentication map data.
   */
  protected function saveAuthMapData(array $authmap_data = []) {
    if ($authname = $this->authname()) {
      $this->externalAuthMap->save($this->loadUser(), 'samlauth', $authname, $authmap_data);
    }
  }

}
