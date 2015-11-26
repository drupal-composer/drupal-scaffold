<?php

namespace DrupalComposer\UpdateScaffold;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;

class PluginScripts {

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * {@inheritdoc}
   */
  public function __construct(Composer $composer, IOInterface $io) {
    $this->io = $io;
    $this->composer = $composer;
  }

  public function postPackage(\Composer\Installer\PackageEvent $event){
    $operation = $event->getOperation();
    if ($operation instanceof InstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }
    elseif ($operation instanceof UninstallOperation) {
      $package = $operation->getPackage();
    }

    if (isset($package) && $package instanceof PackageInterface) {
      $installationManager = $event->getComposer()->getInstallationManager();
      $path = $installationManager->getInstallPath($package);
      $event->getIO()->write(sprintf('Event called: %s, Package: %s (%s), Path: %s',
        $event->getName(),
        $package->getName(),
        $package->getVersion(),
        $path
      ), TRUE);
    }
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
