<?php

namespace Drupal\samlauth\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\samlauth\SamlAuthAccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class \Drupal\samlauth\Plugin\Menu\SamlAuthAccountMenuLink.
 */
class SamlAuthAccountMenuLink extends MenuLinkDefault {

  /**
   * SAML authentication account.
   *
   * @var \Drupal\samlauth\SamlAuthInterface
   */
  protected $samlAuthAccount;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override, SamlAuthAccountInterface $saml_auth_account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);

    $this->samlAuthAccount = $saml_auth_account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('samlauth.account')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->samlAuthAccount->getUsername();
  }

}
