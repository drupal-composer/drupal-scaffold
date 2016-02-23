<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\Handler.
 */

namespace DrupalComposer\DrupalScaffold;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\EventDispatcher\EventDispatcher;

use Composer\Util\Filesystem;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class Handler {

  const PRE_DRUPAL_SCAFFOLD_CMD = 'pre-drupal-scaffold-cmd';
  const POST_DRUPAL_SCAFFOLD_CMD = 'post-drupal-scaffold-cmd';

  /**
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * @var \Composer\Package\PackageInterface
   */
  protected $drupalCorePackage;

  /**
   * Handler constructor.
   *
   * @param Composer $composer
   * @param IOInterface $io
   */
  public function __construct(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  /**
   * @param $operation
   * @return mixed
   */
  protected function getCorePackage($operation) {
    if ($operation instanceof InstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }
    if (isset($package) && $package instanceof PackageInterface && $package->getName() == 'drupal/core') {
      return $package;
    }
    return NULL;
  }

  /**
   * Marks scaffolding to be processed after an install or update command.
   *
   * @param \Composer\Installer\PackageEvent $event
   */
  public function onPostPackageEvent(\Composer\Installer\PackageEvent $event){
    $package = $this->getCorePackage($event->getOperation());
    if ($package) {
      // By explicitiley setting the core package, the onPostCmdEvent() will
      // process the scaffolding automatically.
      $this->drupalCorePackage = $package;
    }
  }

  /**
   * Post install command event to execute the scaffolding.
   *
   * @param \Composer\Script\Event $event
   */
  public function onPostCmdEvent(\Composer\Script\Event $event) {
    // Only install the scaffolding if drupal/core was installed,
    // AND there are no scaffolding files present.
    if (isset($this->drupalCorePackage)) {
      $this->downloadScaffold();
      // Generate the autoload.php file after generating the scaffold files.
      $this->generateAutoload();
    }
  }

  /**
   * Downloads drupal scaffold files for the current process.
   */
  public function downloadScaffold() {
    $drupalCorePackage = $this->getDrupalCorePackage();
    $webroot = $this->getWebRoot();

    // Collect options, excludes and settings files.
    $options = $this->getOptions();
    $excludes = $this->getExcludes();
    $includes = $this->getIncludes();

    // Call any pre-scaffold scripts that may be defined.
    $dispatcher = new EventDispatcher($this->composer, $this->io);
    $dispatcher->dispatch(self::PRE_DRUPAL_SCAFFOLD_CMD);

    // Run Robo
    $robo = new RoboRunner();
    $robo->execute(
      [
        'robo',
        'drupal_scaffold:download',
        $drupalCorePackage->getPrettyVersion(),
        '--source',
        $options['source'],
        '--webroot',
        realpath($webroot),
        '--excludes',
        static::array_to_csv($excludes),
        '--includes',
        static::array_to_csv($includes),
      ]
    );

    // Call post-scaffold scripts.
    $dispatcher->dispatch(self::POST_DRUPAL_SCAFFOLD_CMD);
  }

  /**
   * Execute the specified command and args in a subprocess.
   */
  public static function execute($args) {
    $command = implode(" ", array_map('escapeshellarg', $args));
    passthru($command);
  }

  /**
   * Convert an array into a comma-separated-value string.
   * Items remain unchanged unless they need to be escaped.
   *
   * Compliment of str_getcsv().
   */
  public static function array_to_csv($data, $delimiter = ',', $enclosure = '"', $escape = '\\') {
    return implode(',', array_map(function ($item) use($delimiter, $enclosure, $escape) {
      $has_delimiter = (strpos($item, $delimiter) !== FALSE);
      $escaped_item = str_replace([$enclosure, $escape], ["{$escape}{$enclosure}", "{$escape}{$escape}"], $item);
      return ($has_delimiter || ($item == $escaped_item)) ? $item : "{$escape}{$escaped_item}{$escape}";
    }, $data));
  }

  /**
   * Generate the autoload file at the project root.  Include the
   * autoload file that Composer generated.
   */
  public function generateAutoload() {
    $vendorPath = $this->getVendorPath();
    $webroot = $this->getWebRoot();

    // Calculate the relative path from the webroot (location of the
    // project autoload.php) to the vendor directory.
    $fs = new SymfonyFilesystem();
    $relativeVendorPath = $fs->makePathRelative($vendorPath, realpath($webroot));

    $fs->dumpFile($webroot . "/autoload.php", $this->autoLoadContents($relativeVendorPath));
  }

  /**
   * Build the contents of the autoload file.
   *
   * @return string
   */
  protected function autoLoadContents($relativeVendorPath) {
    $relativeVendorPath = rtrim($relativeVendorPath, '/');

    $autoloadContents = <<<EOF
<?php

/**
 * @file
 * Includes the autoloader created by Composer.
 * This file was generated by drupal-composer/drupal-scaffold.
 * https://github.com/drupal-composer/drupal-scaffold
 *
 * @see composer.json
 * @see index.php
 * @see core/install.php
 * @see core/rebuild.php
 * @see core/modules/statistics/statistics.php
 */

return require __DIR__ . '/$relativeVendorPath/autoload.php';

EOF;
    return $autoloadContents;
  }

  /**
   * Get the path to the 'vendor' directory.
   *
   * @return string
   */
  public function getVendorPath() {
    $config = $this->composer->getConfig();
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($config->get('vendor-dir'));
    $basePath = $filesystem->normalizePath(realpath(getcwd()));
    $vendorPath = $filesystem->normalizePath(realpath($config->get('vendor-dir')));

    return $vendorPath;
  }

  /**
   * Look up the Drupal core package object, or return it from where we cached
   * it in the $drupalCorePackage field.
   *
   * @return PackageInterface
   */
  public function getDrupalCorePackage() {
    if (!isset($this->drupalCorePackage)) {
      $this->drupalCorePackage = $this->getPackage('drupal/core');
    }
    return $this->drupalCorePackage;
  }

  /**
   * Helper to get the robo executable.
   *
   * @return string
   *   The absolute path for the drush directory.
   */
  public function getRoboExecutable() {
    $package = $this->getPackage('codegyre/Robo');
    if ($package) {
      return $this->composer->getInstallationManager()->getInstallPath($package) . '/robo';
    }
  }

  /**
   * Retrieve the path to the web root.
   *
   *  @return string
   */
  public function getWebRoot() {
    $drupalCorePackage = $this->getDrupalCorePackage();
    $installationManager = $this->composer->getInstallationManager();
    $corePath = $installationManager->getInstallPath($drupalCorePackage);
    // Webroot is the parent path of the drupal core installation path.
    $webroot = dirname($corePath);

    return $webroot;
  }

  /**
   * Retrieve a package from the current composer process.
   *
   * @param string $name
   *   Name of the package to get from the current composer installation.
   *
   * @return PackageInterface
   */
  protected function getPackage($name) {
    return $this->composer->getRepositoryManager()->getLocalRepository()->findPackage($name, '*');
  }

  /**
   * Retrieve excludes from optional "extra" configuration.
   *
   * @return array
   */
  protected function getExcludes() {
    return $this->getNamedOptionList('excludes', 'getExcludesDefault');
  }

  /**
   * Retrieve list of additional settings files from optional "extra" configuration.
   *
   * @return array
   */
  protected function getIncludes() {
    return $this->getNamedOptionList('includes', 'getIncludesDefault');
  }

  /**
   * Retrieve a named list of options from optional "extra" configuration.
   * Respects 'omit-defaults', and either includes or does not include the
   * default values, as requested.
   *
   * @return array
   */
  protected function getNamedOptionList($optionName, $defaultFn) {
    $options = $this->getOptions($this->composer);
    $result = array();
    if (empty($options['omit-defaults'])) {
      $result = $this->$defaultFn();
    }
    $result = array_merge($result, (array) $options[$optionName]);

    return $result;
  }

  /**
   * Retrieve excludes from optional "extra" configuration.
   *
   * @return array
   */
  protected function getOptions() {
    $extra = $this->composer->getPackage()->getExtra() + ['drupal-scaffold' => []];
    $options = $extra['drupal-scaffold'] + [
      'omit-defaults' => FALSE,
      'excludes' => [],
      'includes' => [],
      'source' => 'https://ftp.drupal.org/files/projects/drupal-{version}.tar.gz',
    ];
    return $options;
  }

  /**
   * Holds default excludes.
   */
  protected function getExcludesDefault() {
    return [
      '.gitkeep',
      'autoload.php',
      'composer.json',
      'composer.lock',
      'core',
      'drush',
      'example.gitignore',
      'LICENSE.txt',
      'README.txt',
      'vendor',
      'themes',
      'profiles',
      'modules',
      'sites/*',
      'sites/default/*'
    ];
  }

  /**
   * Holds default settings files list.
   */
  protected function getIncludesDefault() {
    return [
      'sites',
      'sites/default',
      'sites/default/default.settings.php',
      'sites/default/default.services.yml',
      'sites/development.services.yml',
      'sites/example.settings.local.php',
      'sites/example.sites.php'
    ];
  }
}
