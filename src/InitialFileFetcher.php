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
        $this->io->write("Going to download the file $filename");
        $this->io->write("  from: $url");
        $this->io->write("  to: $target");
        $this->remoteFilesystem->copy($url, $url, $target);
        // Used to put a new line because the remote file system does not put
        // one.
        $this->io->write('');
      }
    });
  }

}
