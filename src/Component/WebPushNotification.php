<?php

namespace Drupal\web_push_api\Component;

/**
 * The Web Push notification.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Notification/Notification
 */
class WebPushNotification extends WebPushData {

  /**
   * {@inheritdoc}
   */
  public function __construct(string $title) {
    parent::__construct([
      'title' => $title,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function addAction(WebPushNotificationAction $item): self {
    $this->options['actions'][] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBadge(string $item): self {
    $this->options['badge'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody(string $item): self {
    $this->options['body'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(WebPushData $item): self {
    $this->options['data'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDirection(string $item): self {
    \assert(\in_array($item, ['auto', 'ltr', 'rtl'], TRUE));
    $this->options['dir'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setIcon(string $item): self {
    $this->options['icon'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setImage(string $item): self {
    $this->options['image'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage(string $item): self {
    $this->options['lang'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRenotify(bool $item): self {
    $this->options['renotify'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequireInteraction(bool $item): self {
    $this->options['requireInteraction'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSilent(bool $item): self {
    $this->options['silent'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTag(string $item): self {
    $this->options['tag'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp(int $item): self {
    $this->options['timestamp'] = $item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setVibrations(int ...$item): self {
    $this->options['vibrate'] = $item;

    return $this;
  }

}
