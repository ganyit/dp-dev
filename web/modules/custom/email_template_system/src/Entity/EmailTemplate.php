<?php

declare(strict_types=1);

namespace Drupal\email_template_system\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\email_template_system\EmailTemplateInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the email template entity class.
 *
 * @ContentEntityType(
 *   id = "email_template",
 *   label = @Translation("Email template"),
 *   label_collection = @Translation("Email templates"),
 *   label_singular = @Translation("email template"),
 *   label_plural = @Translation("email templates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count email templates",
 *     plural = "@count email templates",
 *   ),
 *   fieldable = TRUE,
 *   handlers = {
 *     "list_builder" = "Drupal\email_template_system\EmailTemplateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\email_template_system\EmailTemplateAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\email_template_system\Form\EmailTemplateForm",
 *       "edit" = "Drupal\email_template_system\Form\EmailTemplateForm",
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
 *   base_table = "email_template",
 *   data_table = "email_template_field_data",
 *   revision_table = "email_template_revision",
 *   revision_data_table = "email_template_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer email_template",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/email-template",
 *     "add-form" = "/email-template/add",
 *     "canonical" = "/email-template/{email_template}",
 *     "edit-form" = "/email-template/{email_template}/edit",
 *     "delete-form" = "/email-template/{email_template}/delete",
 *     "delete-multiple-form" = "/admin/content/email-template/delete-multiple",
 *     "revision" = "/email-template/{email_template}/revision/{email_template_revision}/view",
 *     "revision-delete-form" = "/email-template/{email_template}/revision/{email_template_revision}/delete",
 *     "revision-revert-form" = "/email-template/{email_template}/revision/{email_template_revision}/revert",
 *     "version-history" = "/email-template/{email_template}/revisions",
 *   },
 *   field_ui_base_route = "entity.email_template.settings",
 * )
 */
final class EmailTemplate extends RevisionableContentEntityBase implements EmailTemplateInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the email template was created.'))
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
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the email template was last edited.'))
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
    
      $fields['email_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email Title'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 60)
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE)  // Make it configurable on the "Manage Display" tab
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('form', TRUE)  // Make it configurable on the "Manage Form Display" tab
      ->setDisplayOptions('form', [
        'type' => 'textfield',
        'weight' => 0,
      ]);

      $fields['email_subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email Subject'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE)  // Make it configurable on the "Manage Display" tab
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('form', TRUE)  // Make it configurable on the "Manage Form Display" tab
      ->setDisplayOptions('form', [
        'type' => 'textfield',
        'weight' => 0,
      ]);

      $fields['email_body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Email Content'))
      ->setTranslatable(TRUE)
      ->setSettings([
        'text_processing' => 1, // This enables CKEditor on the field
      ])
      ->setDisplayConfigurable('view', TRUE)  // Make it configurable on the "Manage Display" tab
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('form', TRUE)  // Make it configurable on the "Manage Form Display" tab
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 5,
          'cols' => 60,
        ],
      ]);

    return $fields;
  }

}
