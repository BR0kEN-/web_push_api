<?php declare(strict_types=1);

namespace Drupal\web_push_api\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder.
 */
class WebPushSubscriptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'belongs' => $this->t('Belongs to'),
      'created' => $this->t('Created at'),
      'changed' => $this->t('Updated at'),
      'timezone' => $this->t('User timezone'),
      'browser' => $this->t('User agent'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    \assert($entity instanceof WebPushSubscriptionInterface);
    return [
      'belongs' => $entity->getOwner()->toLink(),
      'created' => $entity->getCreatedDate()->format('Y-m-d, H:i:s'),
      'changed' => $entity->getChangedDate()->format('Y-m-d, H:i:s'),
      'timezone' => $entity->getUserTimeZone()->getName(),
      'browser' => $entity->getUserAgent(),
    ];
  }

}
