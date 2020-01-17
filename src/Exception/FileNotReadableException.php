<?php declare(strict_types=1);

namespace Drupal\web_push_api\Exception;

/**
 * To be thrown when a file is found but has no read permission.
 */
class FileNotReadableException extends FileException {}
