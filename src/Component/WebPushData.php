<?php declare(strict_types=1);

namespace Drupal\web_push_api\Component;

use Drupal\Component\Serialization\Json;

/**
 * The data to send as a notification.
 */
class WebPushData implements \JsonSerializable {

  /**
   * The data.
   *
   * @var array
   */
  protected $options = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $data) {
    $this->options = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return Json::encode($this->options);
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    return $this->options;
  }

}
