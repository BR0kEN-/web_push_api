<?php declare(strict_types=1);

namespace Drupal\web_push_api\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use function t;

/**
 * Defines the entity type.
 *
 * @ContentEntityType(
 *   id = "web_push_subscription",
 *   label = @Translation("Web Push Subscription"),
 *   base_table = "web_push_subscriptions",
 *   admin_permission = "administer web push subscriptions",
 *   handlers = {
 *     "storage" = "Drupal\web_push_api\Entity\WebPushSubscriptionStorage",
 *     "list_builder" = "Drupal\web_push_api\Entity\WebPushSubscriptionListBuilder",
 *     "storage_schema" = "Drupal\web_push_api\Entity\WebPushSubscriptionStorageSchema",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/web-push-api/subscriptions",
 *   },
 * )
 */
class WebPushSubscription extends ContentEntityBase implements WebPushSubscriptionInterface {

  /**
   * {@inheritdoc}
   */
  public function getOwner(): ?UserInterface {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedDate(): \DateTimeInterface {
    return new \DateTime('@' . $this->get('created')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedDate(): \DateTimeInterface {
    return new \DateTime('@' . $this->get('changed')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserAgent(): string {
    return $this->get('user_agent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserTimeZone(): \DateTimeZone {
    return new \DateTimeZone('+' . \abs($this->get('utc_offset')->value));
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint(): string {
    return $this->get('endpoint')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKey(): ?string {
    return $this->get('p256dh')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthToken(): ?string {
    return $this->get('auth')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentEncoding(): ?string {
    return $this->get('encoding')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if (!$this->isNew()) {
      $uid_new = (int) $this->uid->target_id;
      $uid_old = (int) $this->original->uid->target_id;

      // A subscription that has had the owner cannot become anonymous.
      if ($uid_old !== 0 && $uid_new === 0) {
        $this->set('uid', $uid_old);
      }

      $this->set('created', $this->original->created->value);
      $this->set('changed', \Drupal::time()->getRequestTime());
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['endpoint'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Endpoint'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 512)
      ->setDescription(t('The communication endpoint.'));

    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User agent'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setInternal(TRUE)
      ->setSetting('max_length', 512)
      ->setDescription(t('The user agent of a browser that created/changed the subscription.'));

    $fields['encoding'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content encoding'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 12)
      ->setDescription(t('The encoding to encrypt the payload of a push message.'));

    $fields['p256dh'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Public key'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('unique_key', TRUE)
      ->setDescription(t('An <a href="@docs">Elliptic curve Diffie–Hellman</a> public key on the P-256 curve (that is, the NIST secp256r1 elliptic curve).  The resulting key is an uncompressed point in ANSI X9.62 format.', [
        '@docs' => Url::fromUri('https://en.wikipedia.org/wiki/Elliptic-curve_Diffie%E2%80%93Hellman')->toString(TRUE)->getGeneratedUrl(),
      ]));

    $fields['auth'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Authentication token'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('unique_key', TRUE)
      ->setDescription(t('An authentication secret, as described in <a href="@docs">Message Encryption for Web Push</a>.', [
        '@docs' => Url::fromUri('https://tools.ietf.org/html/draft-ietf-webpush-encryption-08')->toString(TRUE)->getGeneratedUrl(),
      ]));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDescription(t('The time the subscription was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDescription(t('The time the subscription was last edited.'));

    $fields['utc_offset'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('UTC offset'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDescription(t('The UTC offset in hours.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setDescription(t('The username of the content author.'));

    return $fields;
  }

}
