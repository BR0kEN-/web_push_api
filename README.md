# Web Push API

This project provides an API for sending notifications to [Push API](https://www.w3.org/TR/push-api/) subscriptions. That's it. It only gives an endpoint for creating/updating/deleting Push API subscriptions and the tools for sending notifications to them. Nothing else.

Do not expect this project to have something from this list:

- A client-side implementation for requesting notification permission and/or subscription to push notifications.
- A UI for creating and/or sending notifications.
- A queue and a worker to handle notifications dispatch.

## Requirements

- PHP 7.2+ and the dependencies of https://github.com/web-push-libs/web-push-php

## Installation

- Download the module and its dependencies.

  ```bash
  composer require drupal/web_push_api
  ```

  Navigate to the directory of `minishlink/web-push` package:

  ```bash
  cd "$(composer config vendor-dir)/minishlink/web-push/"
  ```

  Download and apply the patch:

  ```bash
  curl https://patch-diff.githubusercontent.com/raw/web-push-libs/web-push-php/pull/268.patch -o ./268.patch
  patch -p1 < 268.patch
  ```

- Generate a key-pair for [Voluntary Application Server Identification (VAPID) for Web Push](https://tools.ietf.org/id/draft-ietf-webpush-vapid-03.html). Store `public.key` and `private.key` outside of your document root.

  ```bash configure-keys
  openssl ecparam -genkey -name prime256v1 -out private.pem
  openssl ec -in private.pem -pubout -outform DER|tail -c 65|base64|tr -d '=' |tr '/+' '_-' >> public.key
  openssl ec -in private.pem -outform DER|tail -c +8|head -c 32|base64|tr -d '=' |tr '/+' '_-' >> private.key
  ```

- Install the module (e.g. `drush en web_push_api`) and save the paths to your keys at `/admin/config/services/web-push-api`.

- Do a client-side implementation (example: https://github.com/Minishlink/web-push-php-example). The [subscription on a client must be created with contents from `public.key`](https://developers.google.com/web/fundamentals/push-notifications/subscribing-a-user#subscribe_a_user_with_pushmanager) you've generated before.

## Usage

- Create a subscription on a client and send it to the `/web-push-api/subscription` (`POST`, `PATCH` or `DELETE`).

  ```javascript
  /**
   * @param {ArrayBuffer} buffer
   *
   * @return {string}
   */
  function encodeKey(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
  }

  /**
   * @param {('POST'|'PATCH'|'DELETE')} method
   * @param {PushSubscription} pushSubscription
   */
  async function sync(method, pushSubscription) {
    fetch('https://example.com/web-push-api/subscription', {
      method,
      body: {
        user_agent: navigator.userAgent,
        // The UTC offset in hours.
        utc_offset: new Date().getTimezoneOffset() / 60,
        encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
        endpoint: pushSubscription.endpoint,
        p256dh: encodeKey(pushSubscription.getKey('p256dh')),
        auth: encodeKey(pushSubscription.getKey('p256dh')),
      },
    });
  }
  ```

  The endpoint returns the `{"errors": string[]}` JSON structure. The `errors` will contain violations messages if an action you've undertaken didn't succeed. Otherwise empty.

- Visit `/admin/config/services/web-push-api/subscriptions` and ensure the subscription was stored in Drupal DB.

- Use the module's API to craft and dispatch the notification.

  ```php
  <?php declare(strict_types=1);

  use Drupal\Core\Utility\Error;
  use Drupal\web_push_api\WebPushNotification;
  use Drupal\web_push_api\WebPushNotificationAction;

  $logger = \Drupal::logger('web-push-notification');
  /* @var \Drupal\web_push_api\WebPushFactory $factory */
  $factory = \Drupal::service('web_push_api.factory');
  $webpush = $factory->get();
  $storage = $webpush->getSubscriptionsStorage();
  $notification = (string) (new WebPushNotification('Hello, buddy!'))
    ->addAction(new WebPushNotificationAction('Test action', 'go-go'))
    ->setBody('This is a test notification.');

  foreach ($storage->loadMultiple() as $subscription) {
    $webpush->sendNotification($subscription, $notification);

    foreach ($webpush->flush(100) as $report) {
      if ($report->isSuccess()) {
        $logger->info('ok');
      }
      else {
        $logger->error('fail');

        try {
          $storage->delete([$subscription]);
          $logger->debug('subscription deleted');
        }
        catch (\Exception $e) {
          $logger->debug('unable to delete subscription');
          $logger->error(Error::renderExceptionSafe($e));
        }
      }
    }
  }
  ```

## Similar projects

- https://www.drupal.org/project/browser_push_notification
- https://www.drupal.org/project/web_push_notification
