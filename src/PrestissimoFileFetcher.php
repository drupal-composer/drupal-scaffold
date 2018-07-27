<?php

namespace DrupalComposer\DrupalScaffold;

use Composer\Util\RemoteFilesystem;
use Composer\Config;
use Composer\IO\IOInterface;
use Hirak\Prestissimo\CopyRequest;
use Hirak\Prestissimo\CurlMulti;

/**
 * Extends the default FileFetcher and uses hirak/prestissimo for parallel
 * downloads.
 */
class PrestissimoFileFetcher extends FileFetcher {

  /**
   * @var \Composer\Config
   */
  protected $config;

  /**
   * Constructs this PrestissimoFileFetcher object.
   */
  public function __construct(RemoteFilesystem $remoteFilesystem, $source, IOInterface $io, $progress = TRUE, Config $config) {
    parent::__construct($remoteFilesystem, $source, $io, $progress);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($version, $destination, $override) {
    if (class_exists(CurlMulti::class)) {
      $this->fetchWithPrestissimo($version, $destination, $override);
      return;
    }
    parent::fetch($version, $destination, $override);
  }

  /**
   * Fetch files in parallel.
   */
  protected function fetchWithPrestissimo($version, $destination, $override) {
    $requests = [];

    foreach ($this->filenames as $sourceFilename => $filename) {
      $target = "$destination/$filename";
      if ($override || !file_exists($target)) {
        $url = $this->getUri($sourceFilename, $version);
        $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
        $requests[] = new CopyRequest($url, $target, FALSE, $this->io, $this->config);
      }
    }

    $successCnt = $failureCnt = 0;
    $errors = [];
    $totalCnt = count($requests);
    if ($totalCnt == 0) {
      return;
    }

    $multi = new CurlMulti();
    $multi->setRequests($requests);
    do {
      $multi->setupEventLoop();
      $multi->wait();
      $result = $multi->getFinishedResults();
      $successCnt += $result['successCnt'];
      $failureCnt += $result['failureCnt'];
      if (isset($result['errors'])) {
        $errors += $result['errors'];
      }
      if ($this->progress) {
        foreach ($result['urls'] as $url) {
          $this->io->writeError("  - Downloading <comment>$successCnt</comment>/<comment>$totalCnt</comment>: <info>$url</info>", TRUE);
        }
      }
    } while ($multi->remain());

    $urls = array_keys($errors);
    if ($urls) {
      throw new \Exception('Failed to download ' . implode(", ", $urls));
    }
  }

}
