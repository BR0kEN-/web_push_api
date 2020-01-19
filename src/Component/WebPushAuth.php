<?php declare(strict_types=1);

namespace Drupal\web_push_api\Component;

/**
 * The Web Push authorization.
 *
 * @see \Minishlink\WebPush\WebPush::__construct()
 */
class WebPushAuth extends WebPushData {

  /**
   * Constructs the Web Push authorization.
   *
   * @param string $type
   *   The authorization type.
   * @param array $data
   *   The authorization details.
   */
  public function __construct(string $type, array $data) {
    parent::__construct([$type => $data]);
  }

}
