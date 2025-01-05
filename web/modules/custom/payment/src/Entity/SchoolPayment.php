<?php

declare(strict_types=1);

namespace Drupal\payment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\payment\SchoolPaymentInterface;

/**
 * Defines the school payment entity class.
 *
 * @ContentEntityType(
 *   id = "school_payment",
 *   label = @Translation("school payment"),
 *   label_collection = @Translation("school payments"),
 *   label_singular = @Translation("school payment"),
 *   label_plural = @Translation("school payments"),
 *   label_count = @PluralTranslation(
 *     singular = "@count school payments",
 *     plural = "@count school payments",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\payment\SchoolPaymentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\payment\Form\SchoolPaymentForm",
 *       "edit" = "Drupal\payment\Form\SchoolPaymentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "school_payment",
 *   admin_permission = "administer school_payment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/school-payment",
 *     "add-form" = "/school-payment/add",
 *     "canonical" = "/school-payment/{school_payment}",
 *     "edit-form" = "/school-payment/{school_payment}/edit",
 *     "delete-form" = "/school-payment/{school_payment}/delete",
 *     "delete-multiple-form" = "/admin/content/school-payment/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.school_payment.settings",
 * )
 */
final class SchoolPayment extends ContentEntityBase implements SchoolPaymentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the school payment was created.'))
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
      ->setDescription(t('The time that the school payment was last edited.'));

    return $fields;
  }

}
