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
   * Download scaffold files
   * @param string $version
   *
   * @param array $options
   *   Additional options to override path to webroot and download url.
   */
  public function drupal_scaffoldDownload($version = '8', $options = array(
    'source' => 'http://ftp.drupal.org/files/projects/drupal-{version}.tar.gz',
    'webroot' => 'web',
    'excludes' => '',
    'includes' => '',
  )) {

    $source = str_replace('{version}', $version, $options['source']);
    $webroot = $options['webroot'];
    $confDir = $webroot . '/sites/default';
    $excludes = array_filter(explode(static::DELIMITER_EXCLUDE, $options['excludes']));
    $includes = array_filter(explode(static::DELIMITER_EXCLUDE, $options['includes']));
    $tmpDir = $this->getTmpDir();
    $archiveName = basename($source);
    $archivePath = "$tmpDir/$archiveName";
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

    // Gets the source via wget.
    $this->taskExec('wget')
      ->args($source)
      ->args("--output-file=/dev/null")
      ->args("--output-document=$archivePath")
      ->run();

    // Once this is merged into Robo, we will be able to simply do:
    // $extract = $this->tastExtract($archivePath)->to("$tmpDir/$fetchDirName")->run();
    $extract = new Extract($archivePath);
    $extract->to("$tmpDir/$fetchDirName")->run();

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
    foreach ($includes as $file) {
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
