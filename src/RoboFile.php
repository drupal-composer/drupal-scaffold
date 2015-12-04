<?php
/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\RoboFile
 */

namespace DrupalComposer\DrupalScaffold;


class RoboFile extends \Robo\Tasks {

  const DELIMITER_EXCLUDE = ',';

  /**
   * Build temp folder path for the task.
   *
   * @return string
   */
  protected function getTmpDir() {
    return getcwd() . '/tmp' . rand() . time();
  }

  /**
   * Decide what our fetch directory should be named
   * (temporary location to stash scaffold files before
   * moving them to their final destination in the project).
   *
   * @return string
   */
  protected function getFetchDirName() {
    return 'drupal-8';
  }

  /**
   * Download scaffold files using Drush
   * @param string $version
   *
   * @param array $options
   *   Additional options to override path to drush and webroot.
   */
  public function drupal_scaffoldDrushDownload($version = '8', $options = array(
    'drush' => 'vendor/bin/drush',
    'webroot' => 'web',
    'excludes' => '',
    'settings' => '',
  )) {

    $drush = $options['drush'];
    $webroot = $options['webroot'];
    $excludes = array_filter(explode(static::DELIMITER_EXCLUDE, $options['excludes']));
    $settingsFiles = array_filter(explode(static::DELIMITER_EXCLUDE, $options['settings']));
    $tmpDir = $this->getTmpDir();
    $fetchDirName = $this->getFetchDirName();

    // Gets the source via drush.
    $fetch = $this->taskExec($drush)
      ->args(['dl', 'drupal-' . $version])
      ->args("--root=$tmpDir")
      ->args("--destination=$tmpDir")
      ->args("--drupal-project-rename=$fetchDirName")
      ->args('--quiet')
      ->args('--yes');

    return $this->fetchAndPlaceScaffold($webroot, $tmpDir, $excludes, $settingsFiles, [$fetch]);
  }

  /**
   * Download scaffold files using Http
   * @param string $version
   *
   * @param array $options
   *   Additional options to override path to webroot and download url.
   */
  public function drupal_scaffoldHttpDownload($version = '8', $options = array(
    'source' => 'http://ftp.drupal.org/files/projects/drupal-{version}.tar.gz',
    'webroot' => 'web',
    'excludes' => '',
    'settings' => '',
  )) {

    $source = str_replace('{version}', $version, $options['source']);
    $webroot = $options['webroot'];
    $excludes = array_filter(explode(static::DELIMITER_EXCLUDE, $options['excludes']));
    $settingsFiles = array_filter(explode(static::DELIMITER_EXCLUDE, $options['settings']));
    $tmpDir = $this->getTmpDir();
    $archiveName = basename($source);
    $archivePath = "$tmpDir/$archiveName";
    $fetchDirName = $this->getFetchDirName();

    // Gets the source via wget.
    $fetch = $this->taskExec('wget')
      ->args($source)
      ->args("--output-file=/dev/null")
      ->args("--output-document=$archivePath");

    // Once this is merged into Robo, we will be able to simply do:
    // $extract = $this->tastExtract($archivePath)->to("$tmpDir/$fetchDirName");
    $extract = new Extract($archivePath);
    $extract->to("$tmpDir/$fetchDirName");

    return $this->fetchAndPlaceScaffold($webroot, $tmpDir, $excludes, $settingsFiles, [$fetch, $extract]);
  }

  protected function fetchAndPlaceScaffold($webroot, $tmpDir, $excludes, $settingsFiles, $ops) {
    $confDir = $webroot . '/sites/default';
    $fetchDirName = $this->getFetchDirName();

    $this->stopOnFail();

    $confDirOriginalPerms = FALSE;
    if (is_dir($confDir)) {
      $confDirOriginalPerms = fileperms($confDir);
      $this->taskFilesystemStack()
        ->chmod($confDir, 0755)
        ->run();
    }

    $this->taskFilesystemStack()
      ->mkdir($tmpDir)
      ->mkdir($confDir)
      ->run();

    // Make sure we have an empty temp dir.
    $this->taskCleanDir([$tmpDir])
      ->run();

    // Gets the source and extrat, if necessary
    foreach ($ops as $op) {
      $op->run();
    }

    // Place scaffold files where they belong in the destination
    $rsync = $this->taskRsync()
      ->fromPath("$tmpDir/$fetchDirName/")
      ->toPath($webroot)
      ->args('-a', '-v', '-z')
      ->args('--delete');
    foreach ($excludes as $exclude) {
      $rsync->exclude($exclude);
    }
    $rsync->run();

    // Place any additional listed settings files
    // (e.g. sites/default/example.settings.php)
    foreach ($settingsFiles as $file) {
      $this->taskRsync()
        ->fromPath("$tmpDir/$fetchDirName/" . $file)
        ->toPath($webroot . '/' . $file)
        ->run();
    }

    // Clean up
    $this->taskDeleteDir($tmpDir)
      ->run();
    if ($confDirOriginalPerms) {
      $this->taskFilesystemStack()
        ->chmod($confDir, $confDirOriginalPerms)
        ->run();
    }
  }
}
