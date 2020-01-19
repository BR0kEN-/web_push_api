<?php declare(strict_types=1);

namespace Drupal\web_push_api\Component;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\web_push_api\Entity\WebPushSubscriptionInterface;
use Drupal\web_push_api\Entity\WebPushSubscriptionStorage;
use Minishlink\WebPush\WebPush as WebPushBase;

/**
 * The Web Push manager.
 */
class WebPush extends WebPushBase {

  /**
   * A storage of the "web_push_subscription" entities.
   *
   * @var \Drupal\web_push_api\Entity\WebPushSubscriptionStorage
   */
  protected $subscriptionsStorage;

  /**
   * Constructs the Web Push manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The "entity_type.manager" service.
   * @param \Drupal\web_push_api\Component\WebPushAuth|null $auth
   *   The Web Push authorization.
   * @param array $options
   *   The values for {@see setDefaultOptions()}.
   * @param array $client_options
   *   The values for {@see \GuzzleHttp\Client::__construct()}.
   *
   * @throws \ErrorException
   *   When the authorization is invalid.
   * @throws \Exception
   *   When the HTTP client cannot be constructed.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebPushAuth $auth = NULL, array $options = [], array $client_options = []) {
    $this->subscriptionsStorage = $entity_type_manager->getStorage(WebPushSubscriptionInterface::ENTITY_TYPE);
    parent::__construct($auth === NULL ? [] : $auth->toArray(), $options, 30, $client_options);
    $this->setReuseVAPIDHeaders(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionsStorage(): WebPushSubscriptionStorage {
    return $this->subscriptionsStorage;
  }

}
