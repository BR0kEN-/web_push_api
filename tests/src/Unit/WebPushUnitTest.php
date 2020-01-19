<?php declare(strict_types=1);

namespace Drupal\Tests\web_push_api\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\web_push_api\Component\WebPush;
use Drupal\web_push_api\Component\WebPushAuth;
use Drupal\web_push_api\Component\WebPushAuthVapid;
use Drupal\web_push_api\Component\WebPushNotification;
use Drupal\web_push_api\Entity\WebPushSubscriptionInterface;
use Drupal\web_push_api\Entity\WebPushSubscriptionStorage;

/**
 * Tests the Web Push manager.
 */
class WebPushUnitTest extends UnitTestCase {

  protected const VALID_PUBLIC_KEY = 'BOVKEsFcC58eSsWmUNuQmDHP5CEIApzkXpWrAFAOsaF_BmC_KenBN28Jm0wGO8UxUdhTnaadZs5lBCEkSA3RKFI';
  protected const VALID_PRIVATE_KEY = 'rtNs08fp_UtjaifjHy2Dlk1jU65AoTACgfZJ96bR8Po';

  /**
   * The mocked "entity_type.manager" service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this
      ->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $storage = $this
      ->getMockBuilder(WebPushSubscriptionStorage::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager
      ->expects(static::atLeastOnce())
      ->method('getStorage')
      ->with(WebPushSubscriptionInterface::ENTITY_TYPE)
      ->willReturn($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function test(): void {
    static::assertNotEmpty((new WebPush($this->entityTypeManager))->getSubscriptionsStorage());
    static::assertTrue((new WebPush($this->entityTypeManager, new WebPushAuth('bla', ['a' => 1])))->getReuseVAPIDHeaders());
  }

  /**
   * {@inheritdoc}
   */
  public function testValidVapidAuth(): void {
    $web_push = new WebPush($this->entityTypeManager, new WebPushAuthVapid(static::VALID_PUBLIC_KEY, static::VALID_PRIVATE_KEY, ['subject' => 1]));

    $subscription = $this
      ->getMockBuilder(WebPushSubscriptionInterface::class)
      ->getMock();

    $subscription
      ->expects(static::once())
      ->method('getContentEncoding')
      ->willReturn('aesgcm');

    static::assertTrue($web_push->sendNotification($subscription, (string) new WebPushNotification('Title!')));
    static::assertAttributeCount(1, 'notifications', $web_push);
  }

  /**
   * {@inheritdoc}
   *
   * @dataProvider providerInvalidVapidAuth
   */
  public function testInvalidVapidAuth(string $public_key, string $private_key, string $exception, string $message): void {
    $this->expectException($exception);
    $this->expectExceptionMessage($message);

    new WebPush($this->entityTypeManager, new WebPushAuthVapid($public_key, $private_key, ['subject' => 1]));
  }

  /**
   * {@inheritdoc}
   */
  public function providerInvalidVapidAuth(): array {
    return [
      [
        'public',
        'private',
        \ErrorException::class,
        '[VAPID] Public key should be 65 bytes long when decoded.',
      ],
      [
        static::VALID_PRIVATE_KEY,
        static::VALID_PUBLIC_KEY,
        \ErrorException::class,
        '[VAPID] Public key should be 65 bytes long when decoded.',
      ],
      [
        \implode(\array_fill(0, 65, '-')),
        'asadas',
        /* @see \Base64Url\Base64Url::decode() */
        \InvalidArgumentException::class,
        'Invalid data provided',
      ],
      [
        static::VALID_PUBLIC_KEY,
        'as',
        \ErrorException::class,
        '[VAPID] Private key should be 32 bytes long when decoded.',
      ],
    ];
  }

}
