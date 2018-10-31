<?php

namespace DrupalComposer\DrupalScaffold;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

/**
 * Downloads all required files and writes it to the file system.
 */
class FileFetcher {

  /**
   * @var \Composer\Util\RemoteFilesystem
   */
  protected $remoteFilesystem;

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * @var bool
   *
   * A boolean indicating if progress should be displayed.
   */
  protected $progress;

  /**
   * @var string
   *
   * The source url pattern.
   */
  protected $source;

  /**
   * @var array
   *
   * A list of filename to fetch.
   */
  protected $filenames;

  /**
   * @var \Composer\Util\Filesystem
   *
   * The local filesystem.
   */
  protected $fs;

  /**
   * @var array
   *
   * A list of potential errors.
   */
  protected $errors = [];

  /**
   * Constructs this FileFetcher object.
   */
  public function __construct(RemoteFilesystem $remoteFilesystem, IOInterface $io, $progress = TRUE) {
    $this->remoteFilesystem = $remoteFilesystem;
    $this->io = $io;
    $this->fs = new Filesystem();
    $this->progress = $progress;
  }

  /**
   * Downloads all required files and writes it to the file system.
   */
  public function fetch($version, $destination, $override) {
    $errors = [];

    foreach ($this->filenames as $sourceFilename => $filename) {
      $target = "$destination/$filename";
      if ($override || !file_exists($target)) {
        $url = $this->getUri($sourceFilename, $version);
        $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));

        if ($this->progress) {
          $this->io->writeError("  - <info>$filename</info> (<comment>$url</comment>): ", FALSE);
          try {
            $this->remoteFilesystem->copy($url, $url, $target, $this->progress);
          } catch(\Exception $e) {
            $errors[] = $url;
          }
          // New line because the remoteFilesystem does not put one.
          $this->io->writeError('');
        }
        else {
          try {
            $this->remoteFilesystem->copy($url, $url, $target, $this->progress);
          } catch(\Exception $e) {
            $errors[] = $url;
          }
        }
      }
    }

    if ($errors) {
      $this->addError('Failed to download: ' . "\r\n" . implode("\r\n", $errors));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Set filenames.
   */
  public function setFilenames(array $filenames) {
    $this->filenames = $filenames;
  }

  /**
   * Set source.
   */
  public function setSource($source) {
    $this->source = $source;
  }

  /**
   * Set error.
   */
  public function addError($error) {
    $this->errors[] = $error;
  }

  /**
   * Get errors.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Replace filename and version in the source pattern with their values.
   */
  protected function getUri($filename, $version) {
    $map = [
      '{path}' => $filename,
      '{version}' => $version,
    ];
    return str_replace(array_keys($map), array_values($map), $this->source);
  }

}
