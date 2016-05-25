<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\Tests\PluginTest.
 */

namespace DrupalComposer\DrupalScaffold\Tests;

use Composer\Util\Filesystem;

/**
 * Tests composer plugin functionality.
 */
class PluginTest extends \PHPUnit_Framework_TestCase {

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

    $this->writeTestReleaseTag();
    $this->writeComposerJSON();

    chdir($this->tmpDir);
  }

  /**
   * tearDown
   *
   * @return void
   */
  public function tearDown()
  {
    $this->fs->removeDirectory($this->tmpDir);
    $this->git(sprintf('tag -d "%s"', $this->tmpReleaseTag));
  }

  /**
   * Tests a simple composer install without core, but adding core later.
   */
  public function testComposerInstallAndUpdate() {
    $exampleScaffoldFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'index.php';
    $this->assertFileNotExists($exampleScaffoldFile, 'Scaffold file should not be exist.');
    $this->composer('install');
    $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . 'core', 'Drupal core is installed.');
    $this->assertFileExists($exampleScaffoldFile, 'Scaffold file should be automatically installed.');
    $this->fs->remove($exampleScaffoldFile);
    $this->assertFileNotExists($exampleScaffoldFile, 'Scaffold file should not be exist.');
    $this->composer('drupal-scaffold');
    $this->assertFileExists($exampleScaffoldFile, 'Scaffold file should be installed by "drupal-scaffold" command.');

    foreach (['8.0.1', '8.1.x-dev'] as $version) {
      // We touch a scaffold file, so we can check the file was modified after
      // the scaffold update.
      touch($exampleScaffoldFile);
      $mtime_touched = filemtime($exampleScaffoldFile);
      // Requiring a newer version triggers "composer update"
      $this->composer('require --update-with-dependencies drupal/core:"' . $version .'"');
      clearstatcache();
      $mtime_after = filemtime($exampleScaffoldFile);
      $this->assertNotEquals($mtime_after, $mtime_touched, 'Scaffold file was modified by composer update. (' . $version . ')');
    }

    // We touch a scaffold file, so we can check the file was modified after
    // the custom commandscaffold update.
    touch($exampleScaffoldFile);
    clearstatcache();
    $mtime_touched = filemtime($exampleScaffoldFile);
    $this->composer('drupal-scaffold');
    clearstatcache();
    $mtime_after = filemtime($exampleScaffoldFile);
    $this->assertNotEquals($mtime_after, $mtime_touched, 'Scaffold file was modified by custom command.');
  }

  /**
   * Writes the default composer json to the temp direcoty.
   */
  protected function writeComposerJSON() {
    $json = json_encode($this->composerJSONDefaults(), JSON_PRETTY_PRINT);
    // Write composer.json.
    file_put_contents($this->tmpDir . '/composer.json', $json);
  }

  /**
   * Writes a tag for the current commit, so we can reference it directly in the
   * composer.json.
   */
  protected function writeTestReleaseTag() {
    // Tag the current state.
    $this->tmpReleaseTag = '999.0.' . time();
    $this->git(sprintf('tag -a "%s" -m "%s"', $this->tmpReleaseTag, 'Tag for testing this exact commit'));
  }

  /**
   * Provides the default composer.json data.
   *
   * @return array
   */
  protected function composerJSONDefaults() {
    return array(
      'repositories' => array(
        array(
          'type' => 'vcs',
          'url' => $this->rootDir,
        )
      ),
      'require' => array(
        'drupal-composer/drupal-scaffold' => $this->tmpReleaseTag,
        'composer/installers' => '^1.0.20',
        'drupal/core' => '8.0.0',
      ),
      'scripts' => array(
        'drupal-scaffold' =>  'DrupalComposer\\DrupalScaffold\\Plugin::scaffold'
      ),
      'minimum-stability' => 'dev',
    );
  }

  /**
   * Wrapper for the composer command.
   *
   * @param string $command
   *   Composer command name, arguments and/or options
   */
  protected function composer($command) {
    chdir($this->tmpDir);
    passthru(escapeshellcmd($this->rootDir . '/vendor/bin/composer ' . $command), $exit_code);
    if ($exit_code !== 0) {
      throw new \Exception('Composer returned a non-zero exit code');
    }
  }

  /**
   * Wrapper for git command in the root directory.
   *
   * @param $command
   *   Git command name, arguments and/or options.
   */
  protected function git($command) {
    chdir($this->rootDir);
    passthru(escapeshellcmd('git ' . $command), $exit_code);
    if ($exit_code !== 0) {
      throw new \Exception('Git returned a non-zero exit code');
    }
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
}
