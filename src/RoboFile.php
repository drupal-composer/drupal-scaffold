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
    // $archiveExtension = preg_match("#\.[a-z.]*$#", $archiveName, $matches);

    // Gets the source via wget.
    $fetch = $this->taskExec('wget')
      ->args($source)
      ->args("--output-file=$archivePath");

    return $this->fetchAndPlaceScaffold($webroot, $tmpDir, $excludes, $settingsFiles, [$fetch]);
  }

  protected function fetchAndPlaceScaffold($webroot, $tmpDir, $excludes, $settingsFiles, $ops) {
    $confDir = $webroot . '/sites/default';
    $fetchDirName = $this->getFetchDirName();

    $this->stopOnFail();

    $confDirOriginalPerms = fileperms($confDir);

    $this->taskFilesystemStack()
      ->mkdir($tmpDir)
      ->chmod($confDir, 0755)
      ->run();

    // Make sure we have an empty temp dir.
    $this->taskCleanDir([$tmpDir])
      ->run();

    // Gets the source and extrat, if necessary
    foreach ($ops as $op) {
      $fetch->run();
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
    $this->taskFilesystemStack()
      ->chmod($confDir, $confDirOriginalPerms)
      ->run();
  }

  protected function extractArchive($archivePath, $destination) {
    if (!($mimetype = $this->archiveType($archivePath))) {
      return;
    }

    // We will first extract to $extractLocation and then move to $destination
    $extractLocation = $this->getTmpDir();
    $this->taskFilesystemStack()
      ->mkdir($extractLocation)
      ->mkdir(dirname($destination))
      ->run();

    // Perform the extraction of a zip file.
    if (($mimetype == 'application/zip') || ($mimetype == 'application/x-zip')) {
      $this->taskExec("unzip")
        ->args($archivePath)
        ->args("-d")
        ->args("--destination=$extractLocation")
        ->run();
    }
    // Otherwise we have a possibly-compressed Tar file.
    // If we are not on Windows, then try to do "tar" in a single operation.
    else {
      $tar_compression_flag = '';
      if ($mimetype == 'application/x-gzip') {
        $tar_compression_flag = 'z';
      }
      elseif ($mimetype == 'application/x-bzip2') {
        $tar_compression_flag = 'j';
      }
      $this->taskExec("tar")
        ->args('-C')
        ->args($extractLocation)
        ->args("-x${$tar_compression_flag}f")
        ->args($archivePath)
        ->run();
    }

    // Now, we want to move the extracted files to $destination. There
    // are two possibilities that we must consider:
    //
    // (1) Archived files were encapsulated in a folder with an arbitrary name
    // (2) There was no encapsulating folder, and all the files in the archive
    //     were extracted into $extractLocation
    //
    // In the case of (1), we want to move and rename the encapsulating folder
    // to $destination.
    //
    // In the case of (2), we will just move and rename $extractLocation.
    $filesInExtractLocation = glob("$extractLocation/*");
    $hasEncapsulatingFolder = ((count($filesInExtractLocation) == 1) && is_dir($filesInExtractLocation[0]));
    if ($hasEncapsulatingFolder) {
      $this->taskFilesystemStack()
        ->rename($filesInExtractLocation[0], $destination);
      $this->taskDeleteDir($extractLocation)->run();
    }
    else {
      $this->taskFilesystemStack()
        ->rename($extractLocation, $destination);
    }
    return $return;
  }

  protected function archiveType($archivePath) {
    $content_type = FALSE;
    if (class_exists('finfo')) {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $content_type = $finfo->file($filename);
      // If finfo cannot determine the content type, then we will try other methods
      if ($content_type == 'application/octet-stream') {
        $content_type = FALSE;
      }
    }
    // Examing the file's magic header bytes.
    if (!$content_type) {
      if ($file = fopen($filename, 'rb')) {
        $first = fread($file, 2);
        fclose($file);

        if ($first !== FALSE) {
          // Interpret the two bytes as a little endian 16-bit unsigned int.
          $data = unpack('v', $first);
          switch ($data[1]) {
            case 0x8b1f:
              // First two bytes of gzip files are 0x1f, 0x8b (little-endian).
              // See http://www.gzip.org/zlib/rfc-gzip.html#header-trailer
              $content_type = 'application/x-gzip';
              break;

            case 0x4b50:
              // First two bytes of zip files are 0x50, 0x4b ('PK') (little-endian).
              // See http://en.wikipedia.org/wiki/Zip_(file_format)#File_headers
              $content_type = 'application/zip';
              break;

            case 0x5a42:
              // First two bytes of bzip2 files are 0x5a, 0x42 ('BZ') (big-endian).
              // See http://en.wikipedia.org/wiki/Bzip2#File_format
              $content_type = 'application/x-bzip2';
              break;
          }
        }
      }
    }
    // 3. Lastly if above methods didn't work, try to guess the mime type from
    // the file extension. This is useful if the file has no identificable magic
    // header bytes (for example tarballs).
    if (!$content_type) {
      // Remove querystring from the filename, if present.
      $filename = basename(current(explode('?', $filename, 2)));
      $extension_mimetype = array(
        '.tar.gz'  => 'application/x-gzip',
        '.tgz'     => 'application/x-gzip',
        '.tar'     => 'application/x-tar',
      );
      foreach ($extension_mimetype as $extension => $ct) {
        if (substr($filename, -strlen($extension)) === $extension) {
          $content_type = $ct;
          break;
        }
      }
    }
    return $content_type;
  }
}
