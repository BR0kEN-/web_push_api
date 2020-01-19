<?php declare(strict_types=1);

namespace Drupal\Tests\web_push_api\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\web_push_api\Component\WebPushAuth;
use Drupal\web_push_api\Component\WebPushAuthVapid;
use Drupal\web_push_api\Component\WebPushData;
use Drupal\web_push_api\Component\WebPushNotification;
use Drupal\web_push_api\Component\WebPushNotificationAction;
use org\bovigo\vfs\vfsStream;

/**
 * Tests that data buckets properly format the data.
 *
 * @group web_push_api
 */
class WebPushDataUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function testWebPushData(): void {
    $data = new WebPushData(['a' => 1, 'b' => ['c' => 2]]);
    $json = '{"a":1,"b":{"c":2}}';

    static::assertSame($json, (string) $data);
    static::assertSame($json, Json::encode($data));
    static::assertSame($data->toArray(), $data->jsonSerialize());
  }

  /**
   * {@inheritdoc}
   */
  public function testWebPushAuth(): void {
    $data = ['kk' => 12, 'dd' => new \stdClass()];
    $auth = new WebPushAuth('asdasd', $data);
    static::assertSame(['asdasd' => $data], $auth->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function testWebPushAuthVapid(): void {
    // Generate the subject.
    // -------------------------------------------------------------------------
    $url_generator = $this->createMock(UrlGeneratorInterface::class);
    $url_generator
      ->expects(static::once())
      ->method('generateFromRoute')
      ->with('<front>')
      ->willReturn('https://www.site.com');

    $container = new ContainerBuilder();
    $container->set('url_generator', $url_generator);
    \Drupal::setContainer($container);

    $auth = new WebPushAuthVapid('pubkey', 'private_key');
    static::assertSame([
      'VAPID' => [
        'publicKey' => 'pubkey',
        'privateKey' => 'private_key',
        'subject' => 'https://www.site.com',
      ],
    ], $auth->toArray());

    // Custom subject.
    // -------------------------------------------------------------------------
    $auth = new WebPushAuthVapid('a', 'b', ['subject' => 'mailto:me@site.org']);
    static::assertSame([
      'VAPID' => [
        'subject' => 'mailto:me@site.org',
        'publicKey' => 'a',
        'privateKey' => 'b',
      ],
    ], $auth->toArray());

    // Read keys from files.
    // -------------------------------------------------------------------------
    $fs = vfsStream::setup('root');

    vfsStream::newFile('public.key')->at($fs)->withContent('the public yeah!');
    vfsStream::newFile('private.key')->at($fs)->withContent('the private key');

    $auth = new WebPushAuthVapid('vfs://root/public.key', 'vfs://root/private.key', ['subject' => 'subj']);
    static::assertSame([
      'VAPID' => [
        'subject' => 'subj',
        'publicKey' => 'the public yeah!',
        'privateKey' => 'the private key',
      ],
    ], $auth->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function testWebPushNotification(): void {
    $notification = new WebPushNotification('The notification!');
    $notification->addAction(new WebPushNotificationAction('Title', 'action', '/path/to/icon.png'));
    $notification->setBadge('/path/to/badge.png');
    $notification->setBody('The content.');
    $notification->setData(new WebPushData(['a' => 1]));
    // Ensure the allowed options can be set.
    $notification->setDirection('rtl');
    $notification->setDirection('ltr');
    $notification->setDirection('auto');
    $notification->setIcon('/path/to/notification/icon.jpg');
    $notification->setImage('/path/to/notification/image.jpg');
    $notification->setLanguage('arbitrary');
    $notification->setRenotify(FALSE);
    $notification->setRequireInteraction(TRUE);
    $notification->setSilent(TRUE);
    $notification->setTag('custom-tag');
    $notification->setTimestamp(121391293192);
    $notification->setVibrations(200, 200, 0, 200, 100);

    static::assertSame([
      'title' => 'The notification!',
      'actions' => [
        [
          'icon' => '/path/to/icon.png',
          'title' => 'Title',
          'action' => 'action',
        ],
      ],
      'badge' => '/path/to/badge.png',
      'body' => 'The content.',
      'data' => [
        'a' => 1,
      ],
      'dir' => 'auto',
      'icon' => '/path/to/notification/icon.jpg',
      'image' => '/path/to/notification/image.jpg',
      'lang' => 'arbitrary',
      'renotify' => FALSE,
      'requireInteraction' => TRUE,
      'silent' => TRUE,
      'tag' => 'custom-tag',
      'timestamp' => 121391293192,
      'vibrate' => [200, 200, 0, 200, 100],
    ], Json::decode(Json::encode($notification)));
  }

  /**
   * Tests the assertion in "setDirection" of "WebPushNotification".
   *
   * @param string $direction
   *   The value to set.
   *
   * @expectedException \AssertionError
   *
   * @dataProvider providerWebPushNotificationSetDirectionAssertion
   */
  public function testWebPushNotificationSetDirectionAssertion(string $direction): void {
    $notification = new WebPushNotification('The notification!');
    $notification->setDirection($direction);
  }

  /**
   * {@inheritdoc}
   */
  public function providerWebPushNotificationSetDirectionAssertion(): array {
    return [
      ['bla'],
      ['Rtl'],
      ['rTl'],
      ['rtL'],
      ['Ltr'],
      ['lTr'],
      ['ltR'],
      ['Auto'],
      ['aut0'],
    ];
  }

}
