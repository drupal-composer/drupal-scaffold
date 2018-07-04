<?php

namespace DrupalComposer\DrupalScaffold;

use Composer\Util\RemoteFilesystem;
use Composer\Config;
use Composer\IO\IOInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

/**
 * Extends the default FileFetcher and uses Guzzle for
 * parallel downloads.
 */
class GuzzleFileFetcher extends FileFetcher {

  /**
   * @var \Composer\Config
   */
  protected $config;

  /**
   * Constructs this RollingCurlFileFetcher object.
   */
  public function __construct(RemoteFilesystem $remoteFilesystem, $source, IOInterface $io, $progress = TRUE, Config $config) {
    parent::__construct($remoteFilesystem, $source, $io, $progress);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($version, $destination, $override) {
    $client = new Client();
    $requests = [];
    $metadata = [];

    foreach ($this->filenames as $sourceFilename => $filename) {
      $target = "$destination/$filename";
      if ($override || !file_exists($target)) {
        $url = $this->getUri($sourceFilename, $version);
        $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
        $requests[] = new Request('GET', $url);
        $metadata[] = [
          'url' => $url,
          'target' => $target,
          'filename' => $filename,
        ];
      }
    }

    $pool = new Pool($client, $requests, [
      'concurrency' => 5,
      'fulfilled' => function ($response, $index) use ($metadata) {
        $this->io->writeError("  - <info>{$metadata[$index]['filename']}</info> (<comment>{$metadata[$index]['url']}</comment>): ", TRUE);
        file_put_contents($metadata[$index]['target'], (string) $response->getBody());
      },
      'rejected' => function ($reason, $index) {
        throw $reason;
      },
    ]);

    $promise = $pool->promise();
    $promise->wait();
  }
}
