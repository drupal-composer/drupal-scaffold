<?php
/**
 * @file
 * Contains \DrupalComposer\UpdateScaffold\RoboFile
 */

namespace DrupalComposer\UpdateScaffold;


class RoboFile extends \Robo\Tasks {

  public function status() {
    $this->getOutput()->write('Robo status');
  }

}
