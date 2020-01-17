<?php declare(strict_types=1);

namespace Drupal\web_push_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\web_push_api\Entity\WebPushSubscriptionStorageInjection;
use Drupal\web_push_api\Exception\FileNotExistsException;
use Drupal\web_push_api\Exception\FileNotReadableException;
use Drupal\web_push_api\Form\WebPushSettingsForm;

/**
 * The Web Push factory.
 */
class WebPushFactory {

  use WebPushSubscriptionStorageInjection;

  /**
   * The Web Push API configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get(WebPushSettingsForm::CONFIG);
    $this->storage = $entity_type_manager->getStorage('web_push_subscription');
  }

  /**
   * Returns the service for dispatching notifications.
   *
   * @param string|null $public_key
   *   The path to file with a custom public key.
   * @param string|null $private_key
   *   The path to file with a custom private key.
   * @param array $options
   *   The values for {@see \Drupal\web_push_api\WebPush::setDefaultOptions()}.
   * @param array $client_options
   *   The values for {@see \GuzzleHttp\Client::__construct()}.
   *
   * @return \Drupal\web_push_api\WebPush
   *   The service for dispatching notifications.
   *
   * @throws \Drupal\web_push_api\Exception\FileNotExistsException
   *   When either public or private key does not exist.
   * @throws \Drupal\web_push_api\Exception\FileNotReadableException
   *   When either public or private key has no permission to read.
   * @throws \ErrorException
   *   When the VAPID is invalid.
   * @throws \Exception
   *   When the HTTP client cannot be constructed.
   */
  public function get(string $public_key = NULL, string $private_key = NULL, array $options = [], array $client_options = []): WebPush {
    $auth = [];

    foreach (['publicKey' => $public_key, 'privateKey' => $private_key] as $key => $path) {
      $path = $path ?? $this->config->get($key) ?: '';

      if (!\file_exists($path)) {
        throw new FileNotExistsException($key, $path);
      }

      if (!\is_readable($path)) {
        throw new FileNotReadableException($key, $path);
      }

      $auth[$key] = \trim(\file_get_contents($path));
    }

    $auth['subject'] = Url::fromRoute('<front>')->setAbsolute()->toString();

    $instance = new WebPush($this->storage, ['VAPID' => $auth], $options, NULL, $client_options + ['timeout' => 30]);
    $instance->setReuseVAPIDHeaders(TRUE);

    return $instance;
  }

}
