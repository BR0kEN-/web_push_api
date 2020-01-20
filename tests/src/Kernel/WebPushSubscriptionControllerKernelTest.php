<?php

namespace Drupal\Tests\web_push_api\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\web_push_api\Controller\WebPushSubscriptionController;
use Drupal\web_push_api\Entity\WebPushSubscriptionInterface;
use Drupal\web_push_api\Entity\WebPushSubscriptionStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the entity type manager that allows spoofing storage.
 */
class EntityTypeManagerTest extends EntityTypeManager {

  /**
   * The state of whether test storage should be used.
   *
   * @var bool
   */
  protected $useTestStorage = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $manager) {
    parent::__construct(
      $manager->namespaces,
      $manager->moduleHandler,
      $manager->cacheBackend,
      $manager->stringTranslation,
      $manager->classResolver,
      $manager->entityLastInstalledSchemaRepository
    );

    $this->setContainer($manager->container);
  }

  /**
   * {@inheritdoc}
   */
  public function useTestStorage(bool $state) {
    $this->useTestStorage = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($entity_type_id) {
    $storage = parent::getStorage($entity_type_id);

    if ($this->useTestStorage && $entity_type_id === WebPushSubscriptionInterface::ENTITY_TYPE) {
      return new WebPushSubscriptionStorageTest($storage);
    }

    return $storage;
  }

}

/**
 * Defines the entity storage that throws on "save" and "delete".
 */
class WebPushSubscriptionStorageTest extends WebPushSubscriptionStorage {

  /**
   * {@inheritdoc}
   */
  public function __construct(WebPushSubscriptionStorage $storage) {
    parent::__construct(
      $storage->entityType,
      $storage->database,
      $storage->entityFieldManager,
      $storage->cacheBackend,
      $storage->languageManager,
      $storage->memoryCache,
      $storage->entityTypeBundleInfo,
      $storage->entityTypeManager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    throw new \Exception('save error');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    throw new \Exception('delete error');
  }

}

/**
 * Tests the web controller for creating/updating/deleting subscriptions.
 *
 * @group web_push_api
 * @coversDefaultClass \Drupal\web_push_api\Controller\WebPushSubscriptionController
 */
class WebPushSubscriptionControllerKernelTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The set of straightforward tests.
   */
  protected const TESTS = [
    [
      'POST',
      NULL,
      [
        'The "Content-Type" header must be "application/json".',
        'The "endpoint" must not be empty.',
      ],
    ],
    [
      'POST',
      [],
      [
        'The "endpoint" must not be empty.',
      ],
    ],
    [
      'DELETE',
      [
        'bla' => 'adsa',
      ],
      [
        'The "endpoint" must not be empty.',
      ],
    ],
    // Nothing will happen if a subscription won't be found by the endpoint.
    [
      'DELETE',
      [
        'endpoint' => 'adsa',
      ],
      [],
    ],
    [
      'POST',
      [
        'endpoint' => 'test',
      ],
      [
        'user_agent=This value should not be null.',
        'encoding=This value should not be null.',
        'p256dh=This value should not be null.',
        'auth=This value should not be null.',
        'utc_offset=This value should not be null.',
      ],
    ],
    [
      'POST',
      [
        'utc_offset' => 'test-offset',
        'user_agent' => 'test-ua',
        'endpoint' => 'test-endpoint',
        'encoding' => 'test-encoding',
        'p256dh' => 'test-key',
        'auth' => 'test-auth',
      ],
      [
        'encoding.0.value=<em class="placeholder">Content encoding</em>: may not be longer than 12 characters.',
        'utc_offset.0.value=This value should be of the correct primitive type.',
      ],
    ],
  ];

  protected const SUBSCRIPTION = [
    'utc_offset' => -4,
    'user_agent' => 'test-ua',
    'endpoint' => 'test-endpoint',
    'encoding' => 'the-encoding',
    'p256dh' => 'test-key',
    'auth' => 'test-auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'web_push_api',
  ];

  /**
   * The URL of the "web_push_api.subscription" route.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The decorated "entity_type.manager" service.
   *
   * @var \Drupal\Tests\web_push_api\Kernel\EntityTypeManagerTest
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema(WebPushSubscriptionInterface::ENTITY_TYPE);

    $this->installSchema('user', ['users_data']);
    $this->installSchema('system', ['sequences']);

    $this->entityTypeManager = new EntityTypeManagerTest($this->container->get('entity_type.manager'));
    $this->container->set('entity_type.manager', $this->entityTypeManager);

    $user_storage = $this->entityTypeManager->getStorage('user');
    $user_storage->save($user_storage->create([
      'uid' => 0,
      'name' => 'Anonymous',
      'status' => 0,
    ]));

    $this->endpoint = Url::fromRoute('web_push_api.subscription')->toString(FALSE);

    // Ensure the URL is unchanged. Otherwise someone needs to update docs too.
    static::assertSame('/web-push-api/subscription', $this->endpoint);
  }

  /**
   * Returns the response for a request.
   *
   * @param string $method
   *   The HTTP method.
   * @param array|null $content
   *   The optional payload to send.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   *
   * @throws \Exception
   *   When the HTTP kernel is unable to handle the request.
   */
  protected function sendRequest(string $method, array $content = NULL): Response {
    $server = [];

    if ($content !== NULL) {
      $content = Json::encode($content);
      /* @see \Symfony\Component\HttpFoundation\ServerBag::getHeaders() */
      /* @link https://github.com/symfony/symfony/issues/5074#issuecomment-7299486 */
      $server['CONTENT_TYPE'] = WebPushSubscriptionController::HEADERS['Content-Type'];
    }

    $request = Request::create($this->endpoint, $method, [], [], [], $server, $content);
    $context = $this->container->get('router.request_context');

    $context
      ->fromRequest($request);

    // Without updating the content for this service it remains unchanged
    // since the very first request.
    $this->container
      ->get('router.no_access_checks')
      ->setContext($context);

    return $this->container
      ->get('http_kernel')
      ->handle($request);
  }

  /**
   * Asserts the response.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   * @param string[] $errors
   *   The list of expected errors.
   */
  protected static function assertRequestResponse(Response $response, array $errors = []): void {
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $data = Json::decode($response->getContent());
    static::assertCount(1, $data);
    static::assertSame($errors, $data['errors']);
  }

  /**
   * Asserts the Push API subscription.
   *
   * @param \Drupal\web_push_api\Entity\WebPushSubscriptionInterface $subscription
   *   The subscription to check.
   * @param array $custom
   *   The data to check.
   */
  protected static function assertSubscription(WebPushSubscriptionInterface $subscription, array $custom = ['uid' => '0', 'utc_offset' => '+04:00']): void {
    $data = $custom + static::SUBSCRIPTION;

    static::assertTrue(\is_numeric($subscription->getCreatedDate()->getOffset()));
    static::assertTrue(\is_numeric($subscription->getChangedDate()->getOffset()));
    static::assertSame($data['utc_offset'], $subscription->getUserTimeZone()->getName());
    static::assertSame($data['user_agent'], $subscription->getUserAgent());
    static::assertSame($data['endpoint'], $subscription->getEndpoint());
    static::assertSame($data['encoding'], $subscription->getContentEncoding());
    static::assertSame($data['p256dh'], $subscription->getPublicKey());
    static::assertSame($data['auth'], $subscription->getAuthToken());
    static::assertSame($data['uid'], $subscription->getOwner()->id());
  }

  /**
   * Tests the controller.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function test(): void {
    foreach (['GET', 'PUT', 'HEAD'] as $method) {
      static::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $this->sendRequest($method)->getStatusCode());
    }

    foreach (static::TESTS as [$method, $content, $errors]) {
      static::assertRequestResponse($this->sendRequest($method, $content), $errors);
    }

    $storage = $this->entityTypeManager->getStorage(WebPushSubscriptionInterface::ENTITY_TYPE);

    // Create a subscription.
    // ------------------------------------------------------------------------.
    static::assertRequestResponse($this->sendRequest('POST', static::SUBSCRIPTION));
    static::assertSubscription($storage->loadByEndpoint(static::SUBSCRIPTION['endpoint']));

    // Update the subscription (having a logged in user).
    // ------------------------------------------------------------------------.
    $account = $this->createUser();
    // The uid=0 subscription can receive a UID of a currently logged in user.
    // Thereafter the UID cannot be changed once set to a value above 0. That's
    // how we try deanonymizing subscriptions.
    $this->container->get('current_user')->setAccount($account);

    // Ensure the UID is automatically set to the ID of a logged in user.
    static::assertRequestResponse($this->sendRequest('PATCH', ['uid' => 900, 'auth' => 'aaa-bb', 'utc_offset' => -2] + static::SUBSCRIPTION));
    static::assertSubscription($storage->loadByEndpoint(static::SUBSCRIPTION['endpoint']), [
      'uid' => $account->id(),
      'auth' => 'aaa-bb',
      'utc_offset' => '+02:00',
    ]);

    // Ensure the subscription that has had an owner cannot become unowned.
    // ------------------------------------------------------------------------.
    $this->container->get('current_user')->setAccount(new AnonymousUserSession());
    static::assertRequestResponse($this->sendRequest('PATCH', ['utc_offset' => 0] + static::SUBSCRIPTION));
    static::assertSubscription($storage->loadByEndpoint(static::SUBSCRIPTION['endpoint']), [
      'uid' => $account->id(),
      'utc_offset' => '+00:00',
    ]);

    // Update failed.
    // ------------------------------------------------------------------------.
    $this->entityTypeManager->useTestStorage(TRUE);

    static::assertRequestResponse($this->sendRequest('PATCH', static::SUBSCRIPTION), [
      'Unable to save the subscription.',
    ]);

    // Deletion failed.
    // ------------------------------------------------------------------------.
    static::assertRequestResponse($this->sendRequest('DELETE', static::SUBSCRIPTION), [
      'Unable to delete the subscription.',
    ]);

    // Delete. We can pass the endpoint only.
    // ------------------------------------------------------------------------.
    $this->entityTypeManager->useTestStorage(FALSE);

    static::assertRequestResponse($this->sendRequest('DELETE', ['endpoint' => static::SUBSCRIPTION['endpoint']]));
    static::assertNull($storage->loadByEndpoint(static::SUBSCRIPTION['endpoint']));

    // Tests the storage and list builder.
    // ------------------------------------------------------------------------.
    $subscription = $storage->create(['uid' => $account->id()] + static::SUBSCRIPTION);
    $storage->save($subscription);
    static::assertCount(1, $storage->loadByUserAccount($account));
    $build = $this->entityTypeManager->getListBuilder(WebPushSubscriptionInterface::ENTITY_TYPE)->render();
    static::assertCount(1, $build['table']['#rows']);
    $storage->deleteByUserAccount($account);
    static::assertCount(0, $storage->loadByUserId($account->id()));

    // Tests the storage and list builder (anon user + "deleteByEndpoint()").
    // ------------------------------------------------------------------------.
    $subscription = $storage->create(static::SUBSCRIPTION);
    $storage->save($subscription);
    $build = $this->entityTypeManager->getListBuilder(WebPushSubscriptionInterface::ENTITY_TYPE)->render();
    static::assertCount(1, $build['table']['#rows']);
    $storage->deleteByEndpoint(static::SUBSCRIPTION['endpoint']);
    static::assertCount(0, $storage->loadByUserAccount($account));
  }

}
