<?php
/**
 * @file
 * Contains DrupalComposer\UpdateScaffold\Plugin.
 */

namespace DrupalComposer\UpdateScaffold;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;

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
  public function activate(Composer $composer, IOInterface $io) {
    // We use a separate PluginScripts object. This way we separate
    // functionality and also avoid some debug issues with the plugin being
    // copied on initialisation.
    // @see \Composer\Plugin\PluginManager::registerPackage()
    $this->scripts = new PluginScripts($composer, $io);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      PackageEvents::POST_PACKAGE_INSTALL => 'postPackage',
      PackageEvents::POST_PACKAGE_UPDATE => 'postPackage',
      PackageEvents::POST_PACKAGE_UNINSTALL => 'postPackage',
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
