<?php

namespace DrupalComposer\DrupalScaffold;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\Package;
use Composer\Package\PackageInterface;

class PluginScripts {

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
      $this->downloadScaffold($event->getComposer(), $this->drupalCorePackage);
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
  protected function downloadScaffold(Composer $composer, PackageInterface $drupalCorePackage) {
    $installationManager = $composer->getInstallationManager();
    $corePath = $installationManager->getInstallPath($drupalCorePackage);
    // Webroot is the parent path of the drupal core installation path.
    $webroot = dirname($corePath);

    $robo = new RoboRunner();
    $robo->execute(array(
      'robo',
      'drupal_scaffold:download',
      $drupalCorePackage->getPrettyVersion(),
      '--drush',
      $this->getDrushDir($composer) . '/drush',
      '--webroot',
      $webroot,
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
}
