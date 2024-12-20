<?php

declare(strict_types=1);

namespace Drupal\school;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of students type entities.
 *
 * @see \Drupal\school\Entity\StudentsType
 */
final class StudentsTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No students types available. <a href=":link">Add students type</a>.',
      [':link' => Url::fromRoute('entity.school_students_type.add_form')->toString()],
    );

    return $build;
  }

}
