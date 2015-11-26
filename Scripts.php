<?php

namespace DrupalComposer\DrupalProject;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;

class Scripts {
  public static function hook(\Composer\Installer\PackageEvent $event){
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
    if ($package && $package instanceof PackageInterface) {
      /** @var \Composer\Installer\InstallationManager $installationManager */
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
}
