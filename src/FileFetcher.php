<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\FileFetcher.
 */

namespace DrupalComposer\DrupalScaffold;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

class FileFetcher {

  /**
   * @var \Composer\Util\RemoteFilesystem
   */
  protected $remoteFilesystem;

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  protected $source;
  protected $filenames;
  protected $fs;

  public function __construct(RemoteFilesystem $remoteFilesystem, $source, $filenames = [], IOInterface $io) {
    $this->remoteFilesystem = $remoteFilesystem;
    $this->io = $io;
    $this->source = $source;
    $this->filenames = $filenames;
    $this->fs = new Filesystem();
  }

  public function fetch($version, $destination) {
    array_walk($this->filenames, function ($filename) use ($version, $destination) {
      $url = $this->getUri($filename, $version);
      $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
      $this->io->write("Going to download the file $filename");
      $this->io->write("  from: $url");
      $this->io->write("  to: $destination/$filename");
      $this->remoteFilesystem->copy($url, $url, $destination . '/' . $filename);
      // Used to put a new line because the remote file system does not put
      // one.
      $this->io->write('');
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
