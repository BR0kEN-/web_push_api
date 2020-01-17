<?php declare(strict_types=1);

namespace Drupal\web_push_api\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\Exception\EntityValidationException;
use Drupal\web_push_api\Entity\WebPushSubscriptionInterface;
use Drupal\web_push_api\Entity\WebPushSubscriptionStorage;
use Drupal\web_push_api\WebPushFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller for creating, updating and deleting Push API subscriptions.
 */
class WebPushSubscriptionController extends ControllerBase {

  /**
   * An instance of the "web_push_api.factory" service.
   *
   * @var \Drupal\web_push_api\WebPushFactory
   */
  protected $webPushFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebPushFactory $web_push_factory, TranslationInterface $string_translation) {
    $this->webPushFactory = $web_push_factory;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('web_push_api.factory'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function subscription(Request $request): JsonResponse {
    if (\strpos($request->headers->get('content-type'), 'application/json') !== 0) {
      return static::response((string) $this->t('The "Content-Type" header must be "application/json".'));
    }

    $body = Json::decode($request->getContent());

    if (empty($body['endpoint'])) {
      return static::response((string) $this->t('The "endpoint" must not be empty.'));
    }

    $storage = $this->webPushFactory->getSubscriptionsStorage();

    return static::response(...\call_user_func(
      ['static', $request->getMethod() === 'DELETE' ? 'delete' : 'manage'],
      $storage,
      $storage->loadByEndpoint($body['endpoint']),
      $body
    ));
  }

  /**
   * Returns the JSON response.
   *
   * @param string ...$errors
   *   The optional list of errors.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  protected static function response(string ...$errors): JsonResponse {
    return new JsonResponse([
      'ok' => empty($errors),
      'errors' => $errors,
    ]);
  }

  /**
   * Removes the subscription.
   *
   * @param \Drupal\web_push_api\Entity\WebPushSubscriptionStorage $storage
   *   The subscriptions storage.
   * @param \Drupal\web_push_api\Entity\WebPushSubscriptionInterface|null $subscription
   *   The subscription.
   *
   * @return string[]
   *   The list of errors (empty if none).
   */
  protected static function delete(WebPushSubscriptionStorage $storage, ?WebPushSubscriptionInterface $subscription): array {
    if ($subscription !== NULL) {
      try {
        $storage->delete([$subscription]);
      }
      catch (\Exception $e) {
        return [$e->getMessage()];
      }
    }

    return [];
  }

  /**
   * Creates/updates the subscription.
   *
   * @param \Drupal\web_push_api\Entity\WebPushSubscriptionStorage $storage
   *   The subscriptions storage.
   * @param \Drupal\web_push_api\Entity\WebPushSubscriptionInterface|null $subscription
   *   The subscription.
   * @param iterable $body
   *   The subscription's data.
   *
   * @return string[]
   *   The list of errors (empty if none).
   */
  protected static function manage(WebPushSubscriptionStorage $storage, ?WebPushSubscriptionInterface $subscription, iterable $body): array {
    $subscription = $subscription ?? $storage->create();

    foreach ($body as $key => $value) {
      $subscription->set($key, $value);
    }

    $violations = $subscription->validate();

    if (\count($violations) > 0) {
      return (new EntityValidationException($violations))->getViolationMessages();
    }

    try {
      $storage->save($subscription);
    }
    catch (\Exception $e) {
      return [$e->getMessage()];
    }

    return [];
  }

}
