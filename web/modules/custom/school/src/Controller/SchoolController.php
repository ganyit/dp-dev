<?php

namespace Drupal\school\Controller;

use Drupal\Core\Controller\ControllerBase;

class SchoolController extends ControllerBase {

  public function listAll() {
    $entity = \Drupal::entityTypeManager()->getStorage('students');
    echo '<pre>';
    print_r($entity);
    return [
      '#markup' => $entity,
    ];
  }
}