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
 *
 * @deprecated Use the GuzzleFileFetcher instead.
 */
class PrestissimoFileFetcher extends FileFetcher {

}
