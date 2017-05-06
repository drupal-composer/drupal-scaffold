<?php
/**
 * @file
 * Contains DrupalComposer\DrupalScaffold\Plugin.
 */

namespace DrupalComposer\DrupalScaffold;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin for handling drupal scaffold.
 */
class Plugin implements PluginInterface, EventSubscriberInterface, Capable {

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
  public function getCapabilities() {
    return array(
      'Composer\Plugin\Capability\CommandProvider' => 'DrupalComposer\DrupalScaffold\CommandProvider',
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      PackageEvents::POST_PACKAGE_INSTALL => 'postPackage',
      PackageEvents::POST_PACKAGE_UPDATE => 'postPackage',
      ScriptEvents::POST_UPDATE_CMD => 'postCmd',
      PluginEvents::COMMAND => 'cmdBegins',
    );
  }

  /**
   * Command begins event callback.
   *
   * @param \Composer\Plugin\CommandEvent $event
   */
  public function cmdBegins(\Composer\Plugin\CommandEvent $event) {
    $this->handler->onCmdBeginsEvent($event);
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

}
