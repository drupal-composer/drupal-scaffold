<?php
/**
 * Contains DrupalComposer\UpdateScaffold\Plugin.
 */

namespace DrupalComposer\UpdateScaffold;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

/**
 * Composer plugin for handling drupal scaffold.
 */
class Plugin implements PluginInterface, EventSubscriberInterface {

  /**
   * @var \DrupalComposer\UpdateScaffold\PluginScripts
   */
  protected $scripts;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      ScriptEvents::POST_PACKAGE_INSTALL => 'postPackage',
      ScriptEvents::POST_PACKAGE_UPDATE => 'postPackage',
      ScriptEvents::POST_PACKAGE_UNINSTALL => 'postPackage',
    );
  }

  /**
   * Pre Package event behaviour for backing up preserved paths.
   *
   * @param \Composer\Installer\PackageEvent $event
   */
  public function postPackage(PackageEvent $event) {
    $this->scripts->postPackage($event);
  }
}
