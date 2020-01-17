<?php declare(strict_types=1);

namespace Drupal\web_push_api\Entity;

/**
 * The injection of Web Push subscriptions storage.
 */
trait WebPushSubscriptionStorageInjection {

  /**
   * A storage of the "web_push_subscription" entities.
   *
   * @var \Drupal\web_push_api\Entity\WebPushSubscriptionStorage
   */
  private WebPushSubscriptionStorage $storage;

  /**
   * Returns the storage Web Push API subscriptions.
   *
   * @return \Drupal\web_push_api\Entity\WebPushSubscriptionStorage
   *   The storage Web Push API subscriptions.
   */
  public function getSubscriptionsStorage(): WebPushSubscriptionStorage {
    return $this->storage;
  }

}
