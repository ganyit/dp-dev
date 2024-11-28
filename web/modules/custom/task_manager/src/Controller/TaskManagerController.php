<?php

namespace Drupal\task_manager\Controller;

use Drupal\Core\Controller\ControllerBase;

class TaskManagerController extends ControllerBase {

  public function listAll() {
    $entity = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'tasks']);
    echo '<pre>';
    print_r($entity);
    return [
      '#markup' => $entity,
    ];
  }
}