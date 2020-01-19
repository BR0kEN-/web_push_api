<?php declare(strict_types=1);

namespace Drupal\web_push_api\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the entity storage.
 *
 * @method WebPushSubscriptionInterface create(array $values = [])
 * @method WebPushSubscriptionInterface|null load($id)
 * @method WebPushSubscriptionInterface|null loadRevision($revision_id)
 * @method WebPushSubscriptionInterface|null loadUnchanged($id)
 * @method WebPushSubscriptionInterface[] loadMultiple(array $ids = NULL)
 * @method WebPushSubscriptionInterface[] loadByProperties(array $values = [])
 * @method WebPushSubscriptionInterface[] loadMultipleRevisions(array $revision_ids)
 */
class WebPushSubscriptionStorage extends SqlContentEntityStorage {

  /**
   * Returns the Web Push API subscription, loaded by the given endpoint.
   *
   * @param string $endpoint
   *   The Push API endpoint.
   *
   * @return \Drupal\web_push_api\Entity\WebPushSubscriptionInterface|null
   *   The subscription.
   *
   * @link https://www.w3.org/TR/push-api/#dfn-push-endpoint
   */
  public function loadByEndpoint(string $endpoint): ?WebPushSubscriptionInterface {
    $list = $this->loadByProperties(['endpoint' => $endpoint]);

    return empty($list) ? NULL : \reset($list);
  }

  /**
   * Returns Web Push API subscriptions for a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Generator|\Drupal\web_push_api\Entity\WebPushSubscriptionInterface[]
   *   The list of subscriptions.
   */
  public function loadByUserAccount(AccountInterface $account): \Generator {
    yield from $this->loadByUserId((int) $account->id());
  }

  /**
   * Returns Web Push API subscriptions for a user account.
   *
   * @param int $uid
   *   The ID of a user account.
   *
   * @return \Generator|\Drupal\web_push_api\Entity\WebPushSubscriptionInterface[]
   *   The list of subscriptions.
   */
  public function loadByUserId(int $uid): \Generator {
    yield from $this->loadByProperties([
      'uid' => $uid,
    ]);
  }

  /**
   * Removes user's Web Push API subscriptions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When the operation fails.
   */
  public function deleteByUserAccount(AccountInterface $account): void {
    $this->deleteByUserId((int) $account->id());
  }

  /**
   * Removes user's Web Push API subscriptions.
   *
   * @param int $uid
   *   The ID of a user account.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When the operation fails.
   */
  public function deleteByUserId(int $uid): void {
    $this->delete(\iterator_to_array($this->loadByUserId($uid)));
  }

}
