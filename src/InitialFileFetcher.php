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
        $this->remoteFilesystem->copy($url, $url, $target);
      }
    });
  }

}
