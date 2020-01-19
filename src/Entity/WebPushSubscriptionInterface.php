<?php declare(strict_types=1);

namespace Drupal\web_push_api\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;
use Minishlink\WebPush\SubscriptionInterface;

/**
 * The definition of a Web Push API subscription.
 */
interface WebPushSubscriptionInterface extends SubscriptionInterface, ContentEntityInterface {

  public const ENTITY_TYPE = 'web_push_subscription';

  /**
   * Returns the account of a subscription owner.
   *
   * @return \Drupal\user\UserInterface|null
   *   The account of a subscription owner.
   */
  public function getOwner(): ?UserInterface;

  /**
   * Returns the subscription creation date.
   *
   * @return \DateTimeInterface
   *   The subscription creation date.
   */
  public function getCreatedDate(): \DateTimeInterface;

  /**
   * Returns the subscription last modification date.
   *
   * @return \DateTimeInterface
   *   The subscription last modification date.
   */
  public function getChangedDate(): \DateTimeInterface;

  /**
   * Returns the user agent of a browser the subscription is created for.
   *
   * @return string
   *   The user agent of a browser the subscription is created for.
   */
  public function getUserAgent(): string;

  /**
   * Returns the timezone of a subscription owner.
   *
   * @return \DateTimeZone
   *   The timezone of a subscription owner.
   */
  public function getUserTimeZone(): \DateTimeZone;

}
