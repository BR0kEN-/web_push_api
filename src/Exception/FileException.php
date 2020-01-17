<?php declare(strict_types=1);

namespace Drupal\web_push_api\Exception;

use Drupal\Core\File\Exception\FileException as FileExceptionBase;

/**
 * The file-related exception with an ID.
 */
class FileException extends FileExceptionBase {

  /**
   * The ID of the exception.
   *
   * @var string
   */
  protected string $id;

  /**
   * The path to file.
   *
   * @var string
   */
  protected string $path;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $id, string $path) {
    parent::__construct('The requirement to file is ignored.');
    $this->id = $id;
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(): string {
    return $this->path;
  }

}
