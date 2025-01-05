<?php

declare(strict_types=1);

namespace Drupal\payment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a school payment entity type.
 */
interface SchoolPaymentInterface extends ContentEntityInterface, EntityChangedInterface {

}
