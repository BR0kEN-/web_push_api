<?php declare(strict_types=1);

namespace Drupal\web_push_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\web_push_api\Exception\FileNotExistsException;
use Drupal\web_push_api\Exception\FileNotReadableException;
use Drupal\web_push_api\WebPushFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Web Push API configuration form.
 */
class WebPushSettingsForm extends ConfigFormBase {

  public const CONFIG = 'web_push_api.settings';

  protected const KEYS = [
    'publicKey' => 'Public key (path to file)',
    'privateKey' => 'Private key (path to file)',
  ];

  /**
   * An instance of the "web_push_api.factory" service.
   *
   * @var \Drupal\web_push_api\WebPushFactory
   */
  protected WebPushFactory $webPushFactory;

  /**
   * The path to "web_push_api" module.
   *
   * @var string
   */
  protected string $modulePath;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    WebPushFactory $web_push_factory
  ) {
    parent::__construct($config_factory);
    $this->modulePath = $module_handler->getModule('web_push_api')->getPath();
    $this->webPushFactory = $web_push_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('web_push_api.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(): array {
    return [static::CONFIG];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return static::CONFIG;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config(static::CONFIG);
    $info = [
      $this->t('Please configure a key-pair for <a href="@url" target="_blank">Voluntary Application Server Identification (VAPID)</a> for Web Push.', [
        '@url' => Url::fromUri('https://tools.ietf.org/id/draft-ietf-webpush-vapid-03.html')->toString(),
      ]),
    ];

    if (\preg_match('/```bash\v+?(\s+?openssl.*)```/s', \file_get_contents($this->modulePath . '/README.md'), $matches)) {
      $info[] = $this->t('If you do not have an existing key-pair and do not know how to create such, run these commands in the UNIX command line.');
      $info[] = '<code>' . \implode('<br />', \array_filter(\array_map('\trim', \explode(\PHP_EOL, $matches[1])))) . '</code>';
    }

    $info[] = $this->t('Make sure the key-pair stored in a location, inaccessible from the web. Preferably, outside of the document root. Once done, specify paths to the private and public keys in the fields below.');

    foreach ($info as $line) {
      $form['description'][] = [
        '#tag' => 'p',
        '#type' => 'html_tag',
        '#value' => $line,
      ];
    }

    foreach (static::KEYS as $type => $title) {
      $form[$type] = [
        '#type' => 'textfield',
        '#title' => $this->t($title),
        '#required' => TRUE,
        '#default_value' => $config->get($type),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    try {
      $config = $this->config(static::CONFIG);
      $args = [];

      foreach (static::KEYS as $type => $title) {
        $config->set($type, $args[] = $form_state->getValue($type));
      }

      $this->webPushFactory->get(...$args);
      $config->save(TRUE);
    }
    catch (FileNotExistsException $e) {
      $form_state->setError($form[$e->getId()], $this->t('The "@path" file does not exist.', ['@path' => $e->getPath()]));
    }
    catch (FileNotReadableException $e) {
      $form_state->setError($form[$e->getId()], $this->t('The "@path" file at the specified location is not readable.', ['@path' => $e->getPath()]));
    }
    catch (\Exception $e) {
      $form_state->setError($form, $e->getMessage());
    }
  }

}
