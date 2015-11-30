<?php
/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\RoboFile
 */

namespace DrupalComposer\DrupalScaffold;


class RoboFile extends \Robo\Tasks {

  const DELIMITER_EXCLUDE = ',';

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
    'excludes' => '',
  )) {

    $drush = $options['drush'];
    $webroot = $options['webroot'];
    $excludes = array_filter(explode(static::DELIMITER_EXCLUDE, $options['excludes']));
    $tmpDir = $this->getTmpDir();
    $confDir = $webroot . '/sites/default';

    $this->stopOnFail();

    $confDirOriginalPerms = fileperms($confDir);

    $this->taskFilesystemStack()
      ->mkdir($tmpDir)
      ->chmod($confDir, 0755)
      ->run();

    // Make sure we have an empty temp dir.
    $this->taskCleanDir([$tmpDir])
      ->run();

    // Gets the source via drush.
    $this->taskExec($drush)
      ->args(['dl', 'drupal-' . $version])
      ->args("--root=$tmpDir")
      ->args("--destination=$tmpDir")
      ->args('--drupal-project-rename=drupal-8')
      ->args('--quiet')
      ->args('--yes')
      ->run();

    $rsync = $this->taskRsync()
      ->fromPath("$tmpDir/drupal-8/")
      ->toPath($webroot)
      ->args('-a', '-v', '-z')
      ->args('--delete');
    foreach ($excludes as $exclude) {
      $rsync->exclude($exclude);
    }
    $rsync->run();

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
