<?php

declare(strict_types=1);

namespace Drupal\school\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\school\StudentsInterface;

/**
 * Defines the students entity class.
 *
 * @ContentEntityType(
 *   id = "school_students",
 *   label = @Translation("students"),
 *   label_collection = @Translation("studentss"),
 *   label_singular = @Translation("students"),
 *   label_plural = @Translation("studentss"),
 *   label_count = @PluralTranslation(
 *     singular = "@count studentss",
 *     plural = "@count studentss",
 *   ),
 *   bundle_label = @Translation("students type"),
 *   handlers = {
 *     "list_builder" = "Drupal\school\StudentsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\school\Form\StudentsForm",
 *       "edit" = "Drupal\school\Form\StudentsForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "school_students",
 *   revision_table = "school_students_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer school_students types",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "bundle",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/students",
 *     "add-form" = "/students/add/{school_students_type}",
 *     "add-page" = "/students/add",
 *     "canonical" = "/students/{school_students}",
 *     "edit-form" = "/students/{school_students}/edit",
 *     "delete-form" = "/students/{school_students}/delete",
 *     "delete-multiple-form" = "/admin/content/students/delete-multiple",
 *     "revision" = "/students/{school_students}/revision/{school_students_revision}/view",
 *     "revision-delete-form" = "/students/{school_students}/revision/{school_students_revision}/delete",
 *     "revision-revert-form" = "/students/{school_students}/revision/{school_students_revision}/revert",
 *     "version-history" = "/students/{school_students}/revisions",
 *   },
 *   bundle_entity_type = "school_students_type",
 *   field_ui_base_route = "entity.school_students_type.edit_form",
 * )
 */
final class Students extends RevisionableContentEntityBase implements StudentsInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the students was created.'))
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
      ->setDescription(t('The time that the students was last edited.'));

    return $fields;
  }

}
