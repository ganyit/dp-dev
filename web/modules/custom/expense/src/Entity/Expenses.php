<?php

declare(strict_types=1);

namespace Drupal\expense\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\expense\ExpensesInterface;

/**
 * Defines the expenses entity class.
 *
 * @ContentEntityType(
 *   id = "expense_expenses",
 *   label = @Translation("expenses"),
 *   label_collection = @Translation("expensess"),
 *   label_singular = @Translation("expenses"),
 *   label_plural = @Translation("expensess"),
 *   label_count = @PluralTranslation(
 *     singular = "@count expensess",
 *     plural = "@count expensess",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\expense\ExpensesListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\expense\ExpensesAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\expense\Form\ExpensesForm",
 *       "edit" = "Drupal\expense\Form\ExpensesForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "expense_expenses",
 *   admin_permission = "administer expense_expenses",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/expenses",
 *     "add-form" = "/expenses/add",
 *     "canonical" = "/expenses/{expense_expenses}",
 *     "edit-form" = "/expenses/{expense_expenses}/edit",
 *     "delete-form" = "/expenses/{expense_expenses}/delete",
 *     "delete-multiple-form" = "/admin/content/expenses/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.expense_expenses.settings",
 * )
 */
final class Expenses extends ContentEntityBase implements ExpensesInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the expenses was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the expenses was last edited.'));

    return $fields;
  }

}
