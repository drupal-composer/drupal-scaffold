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
  public function __construct(RemoteFilesystem $remoteFilesystem, IOInterface $io, $progress = TRUE, Config $config) {
    parent::__construct($remoteFilesystem, $io, $progress);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($version, $destination, $override) {
    if (class_exists(CurlMulti::class)) {
      return $this->fetchWithPrestissimo($version, $destination, $override);
    }
    return parent::fetch($version, $destination, $override);
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
      return TRUE;
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
        foreach ($result['errors'] as $url => $error) {
          $this->io->writeError("  - Downloading <comment>$successCnt</comment>/<comment>$totalCnt</comment>: <info>$url</info> (<error>failed</error>)", TRUE);
        }
      }
      if ($this->progress) {
        foreach ($result['urls'] as $url) {
          $this->io->writeError("  - Downloading <comment>$successCnt</comment>/<comment>$totalCnt</comment>: <info>$url</info>", TRUE);
        }
      }
    } while ($multi->remain());

    if ($errors) {
      $this->addError('Failed to download: ' . "\r\n" . implode("\r\n", array_keys($errors)));
      $errors_extra = [];
      foreach($errors as $error) {
        if ($error !== "0: " && !isset($errors_extra[$error])) {
          $errors_extra[$error] = $error;
        }
      }
      if ($errors_extra) {
        $this->addError(implode("\r\n", $errors_extra));
      }
      return FALSE;
    }
    return TRUE;
  }

}
