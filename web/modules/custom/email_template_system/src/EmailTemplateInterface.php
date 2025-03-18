<?php

declare(strict_types=1);

namespace Drupal\email_template_system;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an email template entity type.
 */
interface EmailTemplateInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
