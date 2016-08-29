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
   * @var \Composer\Util\RemoteFilesystem
   */
  protected $remoteFilesystem;

  protected $source;
  protected $filenames;
  protected $fs;

  public function __construct(RemoteFilesystem $remoteFilesystem, $source, $filenames = []) {
    $this->remoteFilesystem = $remoteFilesystem;
    $this->source = $source;
    $this->filenames = $filenames;
    $this->fs = new Filesystem();
  }

  public function fetch($version, $destination) {
    array_walk($this->filenames, function ($filename) use ($version, $destination) {
      $url = $this->getUri($filename, $version);
      $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
      $this->remoteFilesystem->copy($url, $url, $destination . '/' . $filename);
    });
  }

  protected function getUri($filename, $version) {
    $map = [
      '{path}' => $filename,
      '{version}' => $version
    ];
    return str_replace(array_keys($map), array_values($map), $this->source);
  }

}
