<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\Handler.
 */

namespace DrupalComposer\DrupalScaffold;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;

class Handler {

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $drupalCorePackage;

  /**
   * Post package event to collect data of the installed drupal version.
   *
   * @param \Composer\Installer\PackageEvent $event
   */
  public function postPackage(\Composer\Installer\PackageEvent $event){
    $operation = $event->getOperation();
    if ($operation instanceof InstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }

    if (isset($package) && $package instanceof PackageInterface && $package->getName() == 'drupal/core') {
      $this->drupalCorePackage = $package;
    }
  }

  /**
   * Post command event to execute the scaffolding.
   *
   * @param \Composer\Script\Event $event
   */
  public function postCmd(\Composer\Script\Event $event) {
    if (isset($this->drupalCorePackage)) {
      static::downloadScaffold($event->getComposer(), $this->drupalCorePackage);
    }
  }

  /**
   * Script callback for putting in composer scripts.
   *
   * @param \Composer\Script\Event $event
   */
  public static function command(\Composer\Script\Event $event) {
    $composer = $event->getComposer();
    $package = $composer->getRepositoryManager()->getLocalRepository()->findPackage('drupal/core', '*');
    if ($package) {
      static::downloadScaffold($composer, $package);
    }
  }

  /**
   * Downloads drupal scaffold files.
   *
   * @param Composer $composer
   *   The current composer instance.
   * @param PackageInterface $drupalCorePackage
   *  Composer package information about the installed core package.
   */
  protected static function downloadScaffold(Composer $composer, PackageInterface $drupalCorePackage) {
    $installationManager = $composer->getInstallationManager();
    $corePath = $installationManager->getInstallPath($drupalCorePackage);
    // Webroot is the parent path of the drupal core installation path.
    $webroot = dirname($corePath);

    // Collect excludes.
    $excludes = static::getExcludes($composer);

    $robo = new RoboRunner();
    $robo->execute(array(
      'robo',
      'drupal_scaffold:download',
      $drupalCorePackage->getPrettyVersion(),
      '--drush',
      static::getDrushDir($composer) . '/drush',
      '--webroot',
      $webroot,
      '--excludes',
      implode(RoboFile::DELIMITER_EXCLUDE, $excludes),
    ));
  }

  /**
   * Helper to get the drush directory.
   *
   * @param Composer $composer
   *
   * @return string
   *   The absolute path for the drush directory.
   */
  public static function getDrushDir(Composer $composer) {
    $package = $composer->getRepositoryManager()->getLocalRepository()->findPackage('drush/drush', '*');
    if ($package) {
      return $composer->getInstallationManager()->getInstallPath($package);
    }
  }

  /**
   * Retrieve excludes from optional "extra" configuration.
   *
   * @param Composer $composer
   *
   * @return array
   */
  protected static function getExcludes(Composer $composer) {
    $options = static::getOptions($composer);
    $excludes = array();
    if (empty($options['omit-defaults'])) {
      $excludes = static::getExcludesDefault();
    }
    $excludes = array_merge($excludes, (array) $options['excludes']);

    return $excludes;
  }

  /**
   * Retrieve excludes from optional "extra" configuration.
   *
   * @param Composer $composer
   *
   * @return array
   */
  protected static function getOptions(Composer $composer) {
    $extra = $composer->getPackage()->getExtra() + ['drupal-scaffold' => []];
    $options = $extra['drupal-scaffold'] + [
      'omit-defaults' => FALSE,
      'excludes' => [],
    ];
    return $options;
  }

  /**
   * Holds default excludes.
   */
  protected static function getExcludesDefault() {
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
      'sites',
      'themes',
      'profiles',
      'modules',
    ];
  }
}
