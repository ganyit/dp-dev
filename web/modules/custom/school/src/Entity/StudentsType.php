<?php

declare(strict_types=1);

namespace Drupal\school\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the students type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "school_students_type",
 *   label = @Translation("students type"),
 *   label_collection = @Translation("students types"),
 *   label_singular = @Translation("students type"),
 *   label_plural = @Translation("studentss types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count studentss type",
 *     plural = "@count studentss types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\school\Form\StudentsTypeForm",
 *       "edit" = "Drupal\school\Form\StudentsTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\school\StudentsTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer school_students types",
 *   bundle_of = "school_students",
 *   config_prefix = "school_students_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/school_students_types/add",
 *     "edit-form" = "/admin/structure/school_students_types/manage/{school_students_type}",
 *     "delete-form" = "/admin/structure/school_students_types/manage/{school_students_type}/delete",
 *     "collection" = "/admin/structure/school_students_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class StudentsType extends ConfigEntityBundleBase {

  /**
   * The machine name of this students type.
   */
  protected string $id;

  /**
   * The human-readable name of the students type.
   */
  protected string $label;

}
