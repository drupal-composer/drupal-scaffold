<?php
/**
 * @file
 * Contains DrupalComposer\DrupalScaffold\Plugin.
 */

namespace DrupalComposer\DrupalScaffold;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin for handling drupal scaffold.
 */
class Plugin implements PluginInterface, EventSubscriberInterface {

  /**
   * @var \DrupalComposer\DrupalScaffold\Handler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    // We use a separate PluginScripts object. This way we separate
    // functionality and also avoid some debug issues with the plugin being
    // copied on initialisation.
    // @see \Composer\Plugin\PluginManager::registerPackage()
    $this->handler = new Handler($composer, $io);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      PackageEvents::POST_PACKAGE_INSTALL => 'postPackage',
      PackageEvents::POST_PACKAGE_UPDATE => 'postPackage',
      //PackageEvents::POST_PACKAGE_UNINSTALL => 'postPackage',
      //ScriptEvents::POST_INSTALL_CMD => 'postCmd',
      ScriptEvents::POST_UPDATE_CMD => 'postCmd',
    );
  }

  /**
   * Post package event behaviour.
   *
   * @param \Composer\Installer\PackageEvent $event
   */
  public function postPackage(PackageEvent $event) {
    $this->handler->onPostPackageEvent($event);
  }

  /**
   * Post command event callback.
   *
   * @param \Composer\Script\Event $event
   */
  public function postCmd(\Composer\Script\Event $event) {
    $this->handler->onPostCmdEvent($event);
  }

  /**
   * Script callback for putting in composer scripts to download the
   * scaffold files.
   *
   * @param \Composer\Script\Event $event
   */
  public static function scaffold(\Composer\Script\Event $event) {
    $handler = new Handler($event->getComposer(), $event->getIO());
    $handler->downloadScaffold();
    // Generate the autoload.php file after generating the scaffold files.
    $handler->generateAutoload();
  }
}
