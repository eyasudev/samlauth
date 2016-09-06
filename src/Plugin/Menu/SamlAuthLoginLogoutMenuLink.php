<?php

namespace Drupal\samlauth\Plugin\Menu;

use Drupal\user\Plugin\Menu\LoginLogoutMenuLink;

/**
 * Class \Drupal\samlauth\Plugin\Menu\SamlAuthLoginLogoutMenuLink.
 */
class SamlAuthLoginLogoutMenuLink extends LoginLogoutMenuLink {

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    if ($this->currentUser->isAuthenticated()) {
      return 'samlauth.saml_controller_logout';
    }

    return 'samlauth.saml_controller_login';
  }

}
