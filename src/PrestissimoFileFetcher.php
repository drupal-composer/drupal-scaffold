<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\FileFetcher.
 */

namespace DrupalComposer\DrupalScaffold;

use Composer\Config;
use Composer\IO\IOInterface;
use Hirak\Prestissimo\CopyRequest;
use Hirak\Prestissimo\CurlMulti;

class PrestissimoFileFetcher extends FileFetcher {

  /**
   * @var \Composer\Config
   */
  protected $config;

  public function __construct(\Composer\Util\RemoteFilesystem $remoteFilesystem, $source, array $filenames = [], IOInterface $io, $progress = TRUE, Config $config) {
    parent::__construct($remoteFilesystem, $source, $filenames, $io, $progress);
    $this->config = $config;
  }

  public function fetch($version, $destination) {
    if (class_exists(CurlMulti::class)) {
      $this->fetchWithPrestissimo($version, $destination);
      return;
    }
    parent::fetch($version, $destination);
  }

  protected function fetchWithPrestissimo($version, $destination) {
    $requests = [];
    array_walk($this->filenames, function ($filename) use ($version, $destination, &$requests) {
      $url = $this->getUri($filename, $version);
      $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
      $requests[] = new CopyRequest($url, $destination . '/' . $filename, false, $this->io, $this->config);
    });

    $successCnt = $failureCnt = 0;
    $totalCnt = count($requests);

    $multi = new CurlMulti;
    $multi->setRequests($requests);
    do {
      $multi->setupEventLoop();
      $multi->wait();
      $result = $multi->getFinishedResults();
      $successCnt += $result['successCnt'];
      $failureCnt += $result['failureCnt'];
      if ($this->progress) {
        foreach ($result['urls'] as $url) {
          $this->io->writeError("  - Downloading <comment>$successCnt</comment>/<comment>$totalCnt</comment>: <info>$url</info>", true);
        }
      }
    } while ($multi->remain());
  }

}
