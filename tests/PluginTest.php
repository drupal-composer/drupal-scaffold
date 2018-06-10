<?php

namespace DrupalComposer\DrupalScaffold\Tests;

use Composer\Util\Filesystem;
use PHPUnit\Framework\TestCase;

/**
 * Tests composer plugin functionality.
 */
class PluginTest extends TestCase {

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
   * SetUp test.
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
   * TearDown.
   *
   * @return void
   */
  public function tearDown() {
    $this->fs->removeDirectory($this->tmpDir);
    $this->git(sprintf('tag -d "%s"', $this->tmpReleaseTag));
  }

  /**
   * Tests a simple composer install without core, but adding core later.
   */
  public function testComposerInstallAndUpdate() {
    $exampleScaffoldFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'index.php';
    $this->assertFileNotExists($exampleScaffoldFile, 'Scaffold file should not be exist.');
    $this->composer('install --no-dev --prefer-dist');
    $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . 'core', 'Drupal core is installed.');
    $this->assertFileExists($exampleScaffoldFile, 'Scaffold file should be automatically installed.');
    $this->fs->remove($exampleScaffoldFile);
    $this->assertFileNotExists($exampleScaffoldFile, 'Scaffold file should not be exist.');
    $this->composer('drupal:scaffold');
    $this->assertFileExists($exampleScaffoldFile, 'Scaffold file should be installed by "drupal:scaffold" command.');

    foreach (['8.0.1', '8.1.x-dev', '8.3.0', '8.5.x-dev'] as $version) {
      // We touch a scaffold file, so we can check the file was modified after
      // the scaffold update.
      touch($exampleScaffoldFile);
      $mtime_touched = filemtime($exampleScaffoldFile);
      // Requiring a newer version triggers "composer update".
      $this->composer('require --update-with-dependencies --prefer-dist --update-no-dev drupal/core:"' . $version . '"');
      clearstatcache();
      $mtime_after = filemtime($exampleScaffoldFile);
      $this->assertNotEquals($mtime_after, $mtime_touched, 'Scaffold file was modified by composer update. (' . $version . ')');
      switch ($version) {
        case '8.0.1':
        case '8.1.x-dev':
          $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . '.eslintrc');
          $this->assertFileNotExists($this->tmpDir . DIRECTORY_SEPARATOR . '.eslintrc.json');
          $this->assertFileNotExists($this->tmpDir . DIRECTORY_SEPARATOR . '.ht.router.php');
          break;

        case '8.3.0':
          // Note we don't clean up .eslintrc file.
          $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . '.eslintrc');
          $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . '.eslintrc.json');
          $this->assertFileNotExists($this->tmpDir . DIRECTORY_SEPARATOR . '.ht.router.php');
          break;

        case '8.5.x-dev':
          $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . '.eslintrc');
          $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . '.eslintrc.json');
          $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . '.ht.router.php');
          break;
      }
    }

    // We touch a scaffold file, so we can check the file was modified by the
    // custom command.
    file_put_contents($exampleScaffoldFile, 1);
    $this->composer('drupal:scaffold');
    $this->assertNotEquals(file_get_contents($exampleScaffoldFile), 1, 'Scaffold file was modified by custom command.');
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
        ),
      ),
      'require' => array(
        'drupal-composer/drupal-scaffold' => $this->tmpReleaseTag,
        'composer/installers' => '^1.0.20',
        'drupal/core' => '8.0.0',
      ),
      'minimum-stability' => 'dev',
    );
  }

  /**
   * Wrapper for the composer command.
   *
   * @param string $command
   *   Composer command name, arguments and/or options.
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
    mkdir($directory, 0777, TRUE);
  }

}
