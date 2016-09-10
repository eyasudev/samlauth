<?php

namespace Drupal\samlauth\Exception;

use Drupal\Core\Entity\EntityStorageException;

/**
 * Class \Drupal\samlauth\Exception\SamlAuthAccountStorageExecption.
 */
class SamlAuthAccountStorageExecption extends EntityStorageException {

  /**
   * {@inheritdoc}
   */
  public function __construct($code = 0, \Throwable $previous = NULL) {
    $message = $this->setMessageByCode($code);
    parent::__construct($message, $code, $previous);
  }

  /**
   * Set message based on code.
   *
   * @param int $code
   *   The exception error code.
   *
   * @return string
   *   A exception message.
   */
  protected function setMessageByCode($code) {
    switch ($code) {
      case '23000':
        $message = 'Storage: Integrity Constraint Violation - This is
          likely caused by a duplicated user account. Try enabling account
          linking for the given property.';
        break;

      default:
        $message = 'Storage: Error';
        break;
    }

    return $message;
  }

}
