<?php declare(strict_types=1);

namespace Drupal\web_push_api;

/**
 * The Web Push notification action.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/API/NotificationAction
 */
class WebPushNotificationAction extends WebPushData {

  /**
   * {@inheritdoc}
   */
  public function __construct(string $title, string $action, ?string $icon = NULL) {
    parent::__construct([
      'icon' => $icon,
      'title' => $title,
      'action' => $action,
    ]);
  }

}
