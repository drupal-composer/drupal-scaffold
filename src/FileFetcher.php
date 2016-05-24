<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\FileFetcher.
 */

namespace DrupalComposer\DrupalScaffold;

use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;

class FileFetcher {

  protected $client;

  protected $template;

  public function __construct(RemoteFilesystem $client, $source, $filenames = []) {
    $this->client = $client;
    $this->source = $source;
    $this->filenames = $filenames;
    $this->fs = new Filesystem();
  }

  public function fetch($version, $destination) {
    array_walk($this->filenames, function ($filename) use ($version, $destination) {
      $url = $this->getUri($filename, $version);
      $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
      $this->client->copy($url, $url, $destination . '/' . $filename);
    });
    /*
    $promises = array_map(function ($filename) use ($version) {
      return $this->client->getAsync($this->getUri($filename, $version));
    }, array_combine($this->filenames, $this->filenames));

    $results = Promise\unwrap($promises);
    array_walk($results, function (ResponseInterface $response, $filename) use ($destination) {
      $this->fs->ensureDirectoryExists($destination . '/' . dirname($filename));
      file_put_contents($filename, $response->getBody()->getContents());
    });
    */
  }

  protected function getUri($filename, $version) {
    $map = [
      '{path}' => $filename,
      '{version}' => $version
    ];
    return str_replace(array_keys($map), array_values($map), $this->source);
  }

}
