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

  protected $source;
  protected $filenames;
  protected $fs;

  /**
   * Constructs this FileFetcher object.
   */
  public function __construct(RemoteFilesystem $remoteFilesystem, $source, IOInterface $io, $progress = TRUE) {
    $this->remoteFilesystem = $remoteFilesystem;
    $this->io = $io;
    $this->source = $source;
    $this->fs = new Filesystem();
    $this->progress = $progress;
  }

  /**
   * Downloads all required files and writes it to the file system.
   */
  public function fetch($version, $destination, $override) {
    foreach ($this->filenames as $sourceFilename => $filename) {
      $target = "$destination/$filename";
      if ($override || !file_exists($target)) {
        $url = $this->getUri($sourceFilename, $version);
        $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
        if ($this->progress) {
          $this->io->writeError("  - <info>$filename</info> (<comment>$url</comment>): ", FALSE);
          $this->remoteFilesystem->copy($url, $url, $target, $this->progress);
          // Used to put a new line because the remote file system does not put
          // one.
          $this->io->writeError('');
        }
        else {
          $this->remoteFilesystem->copy($url, $url, $target, $this->progress);
        }
      }
    }
  }

  /**
   * Set filenames.
   */
  public function setFilenames(array $filenames) {
    $this->filenames = $filenames;
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
