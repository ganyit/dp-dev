<?php

declare(strict_types=1);

namespace Drupal\expense;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an expenses entity type.
 */
interface ExpensesInterface extends ContentEntityInterface, EntityChangedInterface {

}
