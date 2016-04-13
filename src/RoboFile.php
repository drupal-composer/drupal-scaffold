<?php
/**
 * @file
 * Contains \RoboFile
 */
namespace DrupalComposer\DrupalScaffold;

use DrupalComposer\DrupalScaffold\Extract;

class RoboFile extends \Robo\Tasks {

  /**
   * Build temp folder path for the task.
   *
   * @return string
   */
  protected function getTmpDir() {
    return realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'drupal-scaffold-' . time();
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
    $excludes = array_filter(str_getcsv($options['excludes']));
    $includes = array_filter(str_getcsv($options['includes']));
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

    // Downloads the source and extract
    $this->downloadFile($source, $archivePath);
    $extract = $this->taskExtract($archivePath)->to("$tmpDir/$fetchDirName")->run();

    // Place scaffold files where they belong in the destination
    $this->taskRsync()
      ->fromPath("$tmpDir/$fetchDirName/")
      ->toPath($webroot)
      ->args('-a', '-v', '-z', '--no-group', '--no-owner')
      ->includeFilter($includes)
      ->exclude($excludes)
      ->run();

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
    $this->say("Attempt to download $source to $target");
    $fp = fopen($target, 'w+');
    if (!$fp) {
      $this->yell('Could not open target file ' . $target);
      return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $source);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    $result = curl_exec($ch);
    fclose($fp);
    $details = curl_getinfo($ch);
    curl_close($ch);

    if (!array_key_exists('http_code', $details) || ($details['http_code'] != '200')) {
      $this->yell('Could not download ' . $source);
      return false;
    }
    return true;
  }

}
