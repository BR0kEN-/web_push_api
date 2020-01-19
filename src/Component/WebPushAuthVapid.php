<?php

namespace Drupal\web_push_api\Component;

use Drupal\Core\Url;

/**
 * The Voluntary Application Server Identification (VAPID) authorization.
 *
 * @link https://tools.ietf.org/id/draft-ietf-webpush-vapid-03.html
 * @link https://blog.mozilla.org/services/2016/08/23/sending-vapid-identified-webpush-notifications-via-mozillas-push-service/
 * @link https://blog.mozilla.org/services/2016/04/04/using-vapid-with-webpush/
 * @link https://developers.google.com/web/fundamentals/push-notifications/web-push-protocol
 */
class WebPushAuthVapid extends WebPushAuth {

  /**
   * {@inheritdoc}
   *
   * @see \Minishlink\WebPush\VAPID::validate()
   */
  public function __construct(string $public_key, string $private_key, array $extra = []) {
    foreach (['publicKey' => $public_key, 'privateKey' => $private_key] as $key => $value) {
      $extra[$key] = \is_file($value) ? \trim(\file_get_contents($value)) : $value;
    }

    $extra['subject'] = $extra['subject'] ?? Url::fromRoute('<front>')->setAbsolute()->toString();

    parent::__construct('VAPID', $extra);
  }

}
