<?php
/**
 * @file
 * Contains \DrupalComposer\DrupalScaffold\Tests\HandlerTest.
 */

namespace DrupalComposer\DrupalScaffold\Tests;


use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableArrayRepository;
use DrupalComposer\DrupalScaffold\Handler;

class HandlerTest extends \PHPUnit_Framework_TestCase {

  private static $eightFourTwoIncludes = [
    '.csslintrc' => '.csslintrc',
    '.editorconfig' => '.editorconfig',
    '.eslintignore' => '.eslintignore',
    '.eslintrc.json' => '.eslintrc.json',
    '.gitattributes' => '.gitattributes',
    '.htaccess' => '.htaccess',
    'index.php' => 'index.php',
    'robots.txt' => 'robots.txt',
    'sites/default/default.services.yml' => 'sites/default/default.services.yml',
    'sites/default/default.settings.php' => 'sites/default/default.settings.php',
    'sites/development.services.yml' => 'sites/development.services.yml',
    'sites/example.settings.local.php' => 'sites/example.settings.local.php',
    'sites/example.sites.php' => 'sites/example.sites.php',
    'update.php' => 'update.php',
    'web.config' => 'web.config',
  ];

  private function getComposer($drupalVersion, array $extra = []) {
    $package = new RootPackage('test', '1.0.0', '1.0.0');
    $package->setExtra($extra);
    $composer = new Composer();
    $composer->setPackage($package);

    $io = new NullIO();
    $config = new Config(false);

    $drupalPackage = new Package('drupal/core', $drupalVersion, $drupalVersion);
    $localRepository = new WritableArrayRepository();
    $localRepository->addPackage($drupalPackage);

    $repositoryManager = new RepositoryManager($io, $config);
    $repositoryManager->setLocalRepository($localRepository);
    $composer->setRepositoryManager($repositoryManager);

    return $composer;
  }

  public function getGetIncludesTests() {
    return [
      [
        '8.4.2',
        [],
        self::$eightFourTwoIncludes
      ],
      [
        '8.4.2',
        [
          'drupal-scaffold' => [
            'includes' => ['.csslintrc']
          ]
        ],
        self::$eightFourTwoIncludes
      ],
      [
        '8.4.2',
        [
          'drupal-scaffold' => [
            'includes' => ['.csslintrc' => 'foo']
          ]
        ],
        ['.csslintrc' => 'foo'] + self::$eightFourTwoIncludes
      ],
    ];
  }

  /**
   * @dataProvider getGetIncludesTests
   */
  public function testGetIncludes($drupalVersion, $extra, $expected) {
    $handler = new DummyHandler($this->getComposer($drupalVersion, $extra), new NullIO());
    $actual = $handler->doGetIncludes();
    $this->assertEquals($expected, $actual);
  }

  public function getGetInitialTests() {
    return [
      ['8.4.2', []]
    ];
  }

  /**
   * @dataProvider getGetInitialTests
   */
  public function testGetInitial($drupalVersion, $expected) {
    $io = $this->prophesize(IOInterface::class);

    $handler = new DummyHandler($this->getComposer($drupalVersion), $io->reveal());
    $actual = $handler->doGetInitial();
    $this->assertEquals($expected, $actual);
  }

  public function getGetExcludesTests() {
    return [
      ['8.4.2', []]
    ];
  }

  /**
   * @dataProvider getGetExcludesTests
   */
  public function testGetExcludes($drupalVersion, $expected) {
    $io = $this->prophesize(IOInterface::class);

    $handler = new DummyHandler($this->getComposer($drupalVersion), $io->reveal());
    $actual = $handler->doGetExcludes();
    $this->assertEquals($expected, $actual);
  }

  public function getGetFilesTests() {
    return [
      [
        '8.4.2',
        [],
        self::$eightFourTwoIncludes,
        'Default includes are returned if no excludes are specified.'
      ],
      [
        '8.4.2',
        [
          'drupal-scaffold' => [
            'excludes' => ['.csslintrc']
          ]
        ],
        array_diff_key(self::$eightFourTwoIncludes, ['.csslintrc' => NULL]),
        'Excludes are removed from files when they are specified as a simple array',
      ],
      [
        '8.4.2',
        // Nobody will do this, but we want to make sure it doesn't fail.
        [
          'drupal-scaffold' => [
            'excludes' => [
              '.csslintrc' => 'foo'
            ]
          ]
        ],
        array_diff_key(self::$eightFourTwoIncludes, ['.csslintrc' => '']),
        'Excludes are removed from files when they are specified as an associative array',
      ],
      [
        '8.4.2',
        ['drupal-scaffold' => ['omit-defaults' => true]],
        [],
        'Defaults can be omitted.',
      ],
      [
        '8.4.2',
        [
          'drupal-scaffold' => [
            'omit-defaults' => true,
            'includes' => [
              'foo' => 'bar',
            ],
          ]
        ],
        ['foo' => 'bar'],
        'New includes will be considered'
      ],
      [
        '8.4.2',
        [
          'drupal-scaffold' => [
            'omit-defaults' => true,
            'includes' => [
              'foo' => 'bar',
            ],
            'excludes' => ['foo'],
          ]
        ],
        [],
        'New includes will be considered for exclusion'
      ]
    ];
  }

  /**
   * Test that the getFiles method considers excludes.
   *
   * @dataProvider getGetFilesTests
   */
  public function testGetFiles($drupalVersion, $extra, $expected, $message = '') {
    $io = $this->prophesize(IOInterface::class);

    $handler = new DummyHandler($this->getComposer($drupalVersion, $extra), $io->reveal());
    $actual = $handler->doGetFiles();
    $this->assertEquals($expected, $actual, $message);
  }

}

/**
 * Extends handler to expose public methods that can be used for testing
 * internal behavior.
 */
class DummyHandler extends Handler {

  public function doGetFiles() {
    return $this->getFiles();
  }

  public function doGetIncludes() {
    return $this->getIncludes();
  }

  public function doGetExcludes() {
    return $this->getExcludes();
  }

  public function doGetInitial() {
    return $this->getInitial();
  }
}
