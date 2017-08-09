<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\FileFetcher.
 */

namespace DrupalComposer\DrupalScaffold;

class InitialFileFetcher extends FileFetcher {

  public function fetch($version, $destination) {
    array_walk($this->filenames, function ($filename, $sourceFilename) use ($version, $destination) {
      $target = "$destination/$filename";
      if (!file_exists($target)) {
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
    });
  }

}
