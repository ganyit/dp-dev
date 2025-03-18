<?php

declare(strict_types=1);

namespace Drupal\email_template_system;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the email template entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class EmailTemplateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view email_template'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit email_template'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete email_template'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete email_template revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view email_template revision', 'view email_template']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert email_template revision', 'edit email_template']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create email_template', 'administer email_template'], 'OR');
  }

}
