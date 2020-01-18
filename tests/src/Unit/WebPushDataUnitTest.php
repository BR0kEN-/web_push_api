<?php declare(strict_types=1);

namespace Drupal\Tests\web_push_api\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\UnitTestCase;
use Drupal\web_push_api\WebPushData;
use Drupal\web_push_api\WebPushNotification;
use Drupal\web_push_api\WebPushNotificationAction;

/**
 * Tests that data buckets properly format the data.
 *
 * @group web_push_api
 */
class WebPushDataUnitTest extends UnitTestCase {

  /**
   * Tests that data buckets properly format the data.
   */
  public function testWebPushData(): void {
    $data = new WebPushData(['a' => 1, 'b' => ['c' => 2]]);
    $json = '{"a":1,"b":{"c":2}}';

    static::assertSame($json, (string) $data);
    static::assertSame($json, Json::encode($data));
    static::assertSame($data->toArray(), $data->jsonSerialize());
  }

  /**
   * Tests the notification bucket.
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
