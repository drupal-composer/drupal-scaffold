<?php

namespace DrupalComposer\DrupalScaffold;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\Package;
use Composer\Package\PackageInterface;

class Handler {

  /**
   * @var \Composer\Package\PackageInterface
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
   * Execute the scaffolding update.
   *
   * @param \Composer\Script\Event $event
   */
  public function scaffoldCmd(\Composer\Script\Event $event) {
    $package = $this->getDrupalCorePackage($event->getComposer());
    if ($package) {
      $this->downloadScaffold($event->getComposer(), $package);
    }
  }

  /**
   * Look up the Drupal core package object, or return it from
   * where we cached it in the $drupalCorePackage field.
   */
  protected function getDrupalCorePackage($composer) {
    if (!isset($this->drupalCorePackage)) {
      $this->drupalCorePackage = $composer->getRepositoryManager()->getLocalRepository()->findPackage('drupal/core', '*');
    }
    return $this->drupalCorePackage;
  }

  /**
   * Downloads drupal scaffold files.
   *
   * @param Composer $composer
   *   The current composer instance.
   * @param PackageInterface $drupalCorePackage
   *  Composer package information about the installed core package.
   */
  protected function downloadScaffold(Composer $composer, PackageInterface $drupalCorePackage) {
    $installationManager = $composer->getInstallationManager();
    $corePath = $installationManager->getInstallPath($drupalCorePackage);
    // Webroot is the parent path of the drupal core installation path.
    $webroot = dirname($corePath);

    // Collect excludes.
    $excludes = $this->getExcludes($composer);

    $robo = new RoboRunner();
    $robo->execute(array(
      'robo',
      'drupal_scaffold:download',
      $drupalCorePackage->getPrettyVersion(),
      '--drush',
      $this->getDrushDir($composer) . '/drush',
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
  protected function getExcludes(Composer $composer) {
    $options = $this->getOptions($composer);
    $excludes = array();
    if (empty($options['omit-defaults'])) {
      $excludes = $this->getExcludesDefault();
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
  protected function getOptions(Composer $composer) {
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
      'sites',
      'themes',
      'profiles',
      'modules',
    ];
  }
}
