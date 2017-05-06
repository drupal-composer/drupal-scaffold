<?php

namespace DrupalComposer\DrupalScaffold;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability {

  /**
   * {@inheritdoc}
   */
  public function getCommands() {
    return [
      new DrupalScaffoldCommand()
    ];
  }

}
