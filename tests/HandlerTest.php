<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\Tests\HandlerTest.
 */

namespace DrupalComposer\DrupalScaffold\Tests;

/**
 * Tests composer plugin functionality.
 */
class HandlerTest extends BaseTest {

  /**
   * Tests that files for dev environments are downloaded only in dev mode.
   */
  public function testDevFiles() {
    $exampleScaffoldFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'index.php';
    $developmentScaffoldFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'development.services.yml';
    $this->assertFileNotExists($exampleScaffoldFile, 'Scaffold file should not exist.');
    $this->assertFileNotExists($developmentScaffoldFile, 'Development scaffold file should not exist.');
    $this->composer('install --no-dev');
    $this->assertFileExists($exampleScaffoldFile, 'Scaffold file should exist.');
    $this->assertFileNotExists($developmentScaffoldFile, 'Development scaffold file should not exist.');
    $this->composer('drupal:scaffold');
    $this->assertFileExists($exampleScaffoldFile, 'Scaffold file should exist.');
    $this->assertFileExists($developmentScaffoldFile, 'Development scaffold file should exist.');
  }

  /**
   * Add prefer-stable true to speed up tests.
   *
   * @return array
   */
  protected function composerJSONDefaults() {
    $composerJsonDefault = parent::composerJSONDefaults();
    $composerJsonDefault['prefer-stable'] = TRUE;
    return $composerJsonDefault;
  }

}
