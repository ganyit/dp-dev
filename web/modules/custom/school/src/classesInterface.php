<?php

declare(strict_types=1);

namespace Drupal\school;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a classes entity type.
 */
interface classesInterface extends ContentEntityInterface, EntityChangedInterface {

}
