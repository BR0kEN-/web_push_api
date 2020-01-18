<?php declare(strict_types=1);

namespace Drupal\Tests\web_push_api\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\web_push_api\Component\FileReader;
use org\bovigo\vfs\vfsStream;

/**
 * Tests the file reader.
 *
 * @group web_push_api
 */
class FileReaderUnitTest extends UnitTestCase {

  /**
   * Tests reading a file fragment.
   */
  public function testReadFileFragment(): void {
    vfsStream::newFile('file.txt')
      ->at(vfsStream::setup('root'))
      ->withContent(<<<EOL
This is the super cool content.

And we have to take something from this file.

  bla-bla
and this data
is
  here
   okay, stop

Another line of something.
EOL
      )
      ->url();

    static::assertSame(
      ['and this data', 'is', 'here'],
      FileReader::readFileFragment('vfs://root/file.txt', '/^bla-bla$/', '/^okay,\s+?stop$/')
    );
  }

}
