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
use Composer\Plugin\CommandEvent;
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
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    // We use a separate PluginScripts object. This way we separate
    // functionality and also avoid some debug issues with the plugin being
    // copied on initialisation.
    // @see \Composer\Plugin\PluginManager::registerPackage()
    $this->handler = new Handler($composer, $io);
    $this->io = $io;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      PackageEvents::POST_PACKAGE_INSTALL => 'postPackage',
      PackageEvents::POST_PACKAGE_UPDATE => 'postPackage',
      //PackageEvents::POST_PACKAGE_UNINSTALL => 'postPackage',
      ScriptEvents::POST_INSTALL_CMD => 'postCmd',
      ScriptEvents::POST_UPDATE_CMD => 'postCmd',
    );
  }

  /**
   * Post package event behaviour.
   *
   * @param \Composer\Installer\PackageEvent $event
   */
  public function postPackage(PackageEvent $event) {
    $this->handler->postPackage($event);
  }

  /**
   * Post command event callback.
   *
   * @param \Composer\Script\Event $event
   */
  public function postCmd($event) {
    $this->handler->scaffoldCmd($event);
  }

  /**
   * Script callback for putting in composer scripts.
   *
   * @param \Composer\Script\Event $event
   */
  public static function scaffold(\Composer\Script\Event $event) {
    $composer = $event->getComposer();
    $plugin = static::self($composer);
    $plugin->dispatch($event, __METHOD__);
  }

  /**
   * Script callback for putting in composer scripts.
   *
   * @param \Composer\Script\Event $event
   */
  public function dispatch(\Composer\Script\Event $event, $methodName) {
    $command = "${methodName}Cmd";
    $this->handler->$command($event);
  }

  /**
   * Recover our plugin instance from the pluginManager.
   *
   * Useful for command callbacks, which must be implemented
   * as static methods. Should not be used for other purposes.
   */
  public static function self(Composer $composer) {
    $plugins = $composer->getPluginManager()->getPlugins();
    foreach($plugins as $plugin) {
      if (strpos(get_class($plugin), __NAMESPACE__ . '\\') === 0) {
        return $plugin;
      }
    }
    return NULL; // throw?
  }
}
