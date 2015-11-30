# drupal-scaffold

Composer plugin for automatically downloading Drupal scaffold files (like
`index.php`, `update.php`, …) when using `drupal/core` via Composer.

## Usage

`composer require drupal-composer/drupal-scaffold:dev-master` in your composer
project before installing or updating `drupal/core`.

## Configuration

You can configure the plugin with providing some settings in the `extra` section
of your root `composer.json`.

```json
{
  "extra": {
      "drupal-scaffold-excludes": [
        'google123.html',
        'robots.txt'
      ],
      "drupal-scaffold-excludes-omit-defaults": false,
    }
}
```

With `drupal-scaffold-excludes` you can provide additional paths, that should
be excluded when updating the project.

With `drupal-scaffold-excludes-omit-defaults` setting to `true`, the plugin does
not exclude crucial default paths. Make sure you add relevant paths back to 
`drupal-scaffold-excludes` manually, when using this setting.

The defaults are 
```
.gitkeep
autoload.php
composer.json
composer.lock
core
drush
example.gitignore
LICENSE.txt
README.txt
vendor
sites
themes
profiles
modules
```
