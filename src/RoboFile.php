<?php
/**
 * @file
 * Contains \DrupalComposer\UpdateScaffold\RoboFile
 */

namespace DrupalComposer\UpdateScaffold;


class RoboFile extends \Robo\Tasks {

  /**
   * Build temp folder path for the task.
   *
   * @return string
   */
  protected function getTmpDir() {
    return getcwd() . '/tmp' . time();
  }

  /**
   * Downloads
   * @param null $version
   *
   * @param array $options
   *   Additional options to override path to rush and webroot.
   */
  public function drupal_scaffoldDownload($version = '8', $options = array(
    'drush' => 'vendor/bin/drush',
    'webroot' => 'web',
  )) {

    $drush = $options['drush'];
    $webroot = $options['webroot'];
    $tmpDir = $this->getTmpDir();
    $confDir = $webroot . '/sites/default';

    $this->stopOnFail();

    $confDirOriginalPerms = fileperms($confDir);

    $this->taskFilesystemStack()
      ->mkdir($tmpDir)
      ->chmod($confDir, 0755)
      ->run();

    $this->taskCleanDir([$tmpDir])
      ->run();

    $this->taskExec($drush)
      ->args(['dl', 'drupal-' . $version])
      ->args("--root=$tmpDir")
      ->args("--destination=$tmpDir")
      ->args('--drupal-project-rename=drupal-8')
      ->args('--quiet')
      ->args('--yes')
      ->run();

    $this->taskRsync()
      ->fromPath("$tmpDir/drupal-8/")
      ->toPath($webroot)
      ->args('-a', '-v', '-z')
      ->args('--delete')
      ->exclude('.gitkeep')
      ->exclude('autoload.php')
      ->exclude('composer.json')
      ->exclude('composer.lock')
      ->exclude('core')
      ->exclude('drush')
      ->exclude('example.gitignore')
      ->exclude('LICENSE.txt')
      ->exclude('README.txt')
      ->exclude('vendor')
      ->exclude('sites')
      ->exclude('themes')
      ->exclude('profiles')
      ->exclude('modules')
      ->run();

    $default_settings = [
      'sites/default/default.settings.php',
      'sites/default/default.services.yml',
      'sites/example.settings.local.php',
      'sites/example.sites.php'
    ];

    foreach ($default_settings as $file) {
      $this->taskRsync()
        ->fromPath("$tmpDir/drupal-8/" . $file)
        ->toPath($webroot . '/' . $file)
        ->run();
    }

    $this->taskDeleteDir($tmpDir)
      ->run();

    $this->taskFilesystemStack()
      ->chmod($confDir, $confDirOriginalPerms)
      ->run();
  }

}
