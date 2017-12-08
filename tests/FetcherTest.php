<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\Tests\FetcherTest.
 */

namespace DrupalComposer\DrupalScaffold\Tests;

use Composer\Downloader\TransportException;
use Composer\IO\NullIO;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;
use DrupalComposer\DrupalScaffold\FileFetcher;
use DrupalComposer\DrupalScaffold\InitialFileFetcher;

class FetcherTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var \Composer\Util\Filesystem
   */
  protected $fs;

  /**
   * @var string
   */
  protected $tmpDir;

  /**
   * @var string
   */
  protected $rootDir;

  /**
   * @var string
   */
  protected $tmpReleaseTag;

  /**
   * SetUp test
   */
  public function setUp() {
    $this->rootDir = realpath(realpath(__DIR__ . '/..'));

    // Prepare temp directory.
    $this->fs = new Filesystem();
    $this->tmpDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'drupal-scaffold';
    $this->ensureDirectoryExistsAndClear($this->tmpDir);

    chdir($this->tmpDir);
  }

  /**
   * Makes sure the given directory exists and has no content.
   *
   * @param string $directory
   */
  protected function ensureDirectoryExistsAndClear($directory) {
    if (is_dir($directory)) {
      $this->fs->removeDirectory($directory);
    }
    mkdir($directory, 0777, true);
  }

  public function testFetch() {
    $fetcher = new FileFetcher(new RemoteFilesystem(new NullIO()), 'http://cgit.drupalcode.org/drupal/plain/{path}?h={version}', ['.htaccess', 'sites/default/default.settings.php']);
    $fetcher->fetch('8.1.1', $this->tmpDir);
    $this->assertFileExists($this->tmpDir . '/.htaccess');
    $this->assertFileExists($this->tmpDir . '/sites/default/default.settings.php');
  }

  public function testInitialFetch() {
    $fetcher = new InitialFileFetcher(new RemoteFilesystem(new NullIO()), 'http://cgit.drupalcode.org/drupal/plain/{path}?h={version}', ['sites/default/default.settings.php' => 'sites/default/settings.php']);
    $fetcher->fetch('8.1.1', $this->tmpDir);
    $this->assertFileExists($this->tmpDir . '/sites/default/settings.php');
  }
}
