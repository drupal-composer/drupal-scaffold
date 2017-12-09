<?php

namespace DrupalComposer\DrupalScaffold\Tests;

/**
 * Tests composer plugin functionality.
 */
class PluginTest extends BaseTest {

  /**
   * Tests a simple composer install without core, but adding core later.
   */
  public function testComposerInstallAndUpdate() {
    $exampleScaffoldFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'index.php';
    $this->assertFileNotExists($exampleScaffoldFile, 'Scaffold file should not be exist.');
    $this->composer('install --prefer-dist');
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
      $this->composer('require --update-with-dependencies --prefer-dist drupal/core:"' . $version . '"');
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

}
