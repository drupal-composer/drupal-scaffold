<?php
/**
 * @file
 * Contains \RoboFile
 */

use DrupalComposer\DrupalScaffold\Extract;

class RoboFile extends \Robo\Tasks {

  const DELIMITER_EXCLUDE = ',';

  /**
   * Build temp folder path for the task.
   *
   * @return string
   */
  protected function getTmpDir() {
    return realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . '/drupal-scaffold-drupal8-' . time();
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
    'source' => 'https://ftp.drupal.org/files/projects/drupal-{version}.tar.gz',
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

    $fs = $this->taskFilesystemStack()
      ->mkdir($tmpDir);

    if (file_exists($confDir)) {
      $confDirOriginalPerms = fileperms($confDir);
    }
    else {
      $confDirOriginalPerms = 0755;
      $fs->mkdir($confDir);
    }

    $fs->chmod($confDir, 0755)
      ->run();

    // Make sure we have an empty temp dir.
    $this->taskCleanDir([$tmpDir])
      ->run();

    // Downloads the source.
    $this->downloadFile($source, $archivePath);

    // Once this is merged into Robo, we will be able to simply do:
    // $extract = $this->tastExtract($archivePath)->to("$tmpDir/$fetchDirName")->run();
    $extract = new Extract($archivePath);
    $extract->to("$tmpDir/$fetchDirName")->run();

    // Place scaffold files where they belong in the destination
    $rsync = $this->taskRsync()
      ->fromPath("$tmpDir/$fetchDirName/")
      ->toPath($webroot)
      ->args('-a', '-v', '-z');
    foreach ($includes as $include) {
      $rsync->option('include', escapeshellarg($include));
    }
    foreach ($excludes as $exclude) {
      $rsync->exclude($exclude);
    }
    $rsync->run();

    // Clean up
    $this->taskDeleteDir($tmpDir)
      ->run();
    if ($confDirOriginalPerms) {
      $this->taskFilesystemStack()
        ->chmod($confDir, $confDirOriginalPerms)
        ->run();
    }
  }

  /**
   * Download file from a source to a target.
   *
   * @param string $source
   * @param string $target
   */
  protected function downloadFile($source, $target) {
    $client = new \GuzzleHttp\Client(['base_uri' => dirname($source) . "/"]);
    $response = $client->request('GET', basename($source), ['sink' => $target]);
  }

}
