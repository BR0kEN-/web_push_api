<?php declare(strict_types=1);

namespace Drupal\web_push_api;

use Drupal\web_push_api\Entity\WebPushSubscriptionStorage;
use Drupal\web_push_api\Entity\WebPushSubscriptionStorageInjection;
use Minishlink\WebPush\WebPush as WebPushBase;

/**
 * The Web Push with attached subscriptions storage.
 *
 * @see \Drupal\web_push_api\WebPushFactory::get()
 */
class WebPush extends WebPushBase {

  use WebPushSubscriptionStorageInjection;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebPushSubscriptionStorage $storage, ...$args) {
    parent::__construct(...$args);
    $this->storage = $storage;
  }

}
