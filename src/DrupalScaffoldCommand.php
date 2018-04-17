<?php

namespace DrupalComposer\DrupalScaffold;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The "drupal:scaffold" command class.
 *
 * Downloads scaffold files and generates the autoload.php file.
 */
class DrupalScaffoldCommand extends BaseCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drupal:scaffold')
      ->setDescription('Update the Drupal scaffold files.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $handler = new Handler($this->getComposer(), $this->getIO());
    $handler->downloadScaffold();
    // Generate the autoload.php file after generating the scaffold files.
    $handler->generateAutoload();
  }

}
