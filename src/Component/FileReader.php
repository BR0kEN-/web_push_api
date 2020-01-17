<?php declare(strict_types=1);

namespace Drupal\web_push_api\Component;

/**
 * The file reader.
 */
trait FileReader {

  /**
   * Returns the lines between "$start_pattern" and "$stop_pattern".
   *
   * @param string $path
   *   The path to a file to read.
   * @param string $start_pattern
   *   The pattern to start collecting lines.
   * @param string $stop_pattern
   *   The pattern to stop collecting lines.
   *
   * @return string[]
   *   The file fragment.
   *
   * @throws \RuntimeException
   *   When the filename cannot be opened.
   * @throws \LogicException
   *   When the filename is a directory.
   */
  public static function readFileFragment(string $path, string $start_pattern, string $stop_pattern): array {
    $read = FALSE;
    $data = [];

    foreach (new \SplFileObject($path) as $line) {
      $line = \trim($line);

      if ($read) {
        if (\preg_match($stop_pattern, $line) === 1) {
          break;
        }
      }
      else {
        $read = \preg_match($start_pattern, $line) === 1;
        continue;
      }

      $data[] = $line;
    }

    return $data;
  }

}
