<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\FileFetcher.
 */

namespace DrupalComposer\DrupalScaffold;

use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

class FileFetcher {

  /**
   * @var RemoteFilesystem
   */
  protected $remoteFilesystem;

  protected $source;

  /**
   * @var array
   */
  protected $filenames;

  /**
   * @var Filesystem
   */
  protected $fs;

  /**
   * FileFetcher constructor.
   * @param RemoteFilesystem $remoteFilesystem
   * @param $source
   * @param array $filenames
   */
  public function __construct(RemoteFilesystem $remoteFilesystem, $source, array $filenames = []) {
    $this->remoteFilesystem = $remoteFilesystem;
    $this->source = $source;
    $this->filenames = $filenames;
    $this->fs = new Filesystem();
  }

  /**
   * @param $version
   * @param $destination
   */
  public function fetch($version, $destination) {
    array_walk($this->filenames, function ($filename) use ($version, $destination) {
      $url = $this->getUri($filename, $version);
      $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
      $this->remoteFilesystem->copy($url, $url, $destination . '/' . $filename);
    });
  }

  /**
   * @param $filename
   * @param $version
   * @return mixed
   */
  protected function getUri($filename, $version) {
    $map = [
      '{path}' => $filename,
      '{version}' => $version
    ];
    return str_replace(array_keys($map), array_values($map), $this->source);
  }

}
