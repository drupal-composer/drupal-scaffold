<?php
/**
 * Created by PhpStorm.
 * User: derhasi
 * Date: 26.11.15
 * Time: 20:37
 */

namespace DrupalComposer\UpdateScaffold;


class RoboRunner extends \Robo\Runner {

  /**
   * Overrides \Robo\Runner\loadRoboFile() so we do not need to provide the
   * exact location of the file.
   */
  protected function loadRoboFile()
  {
    $this->roboClass = '\DrupalComposer\UpdateScaffold\RoboFile';
    return true;
  }

}
