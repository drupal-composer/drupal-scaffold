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
        "google123.html",
        "robots.txt"
      ],
      "drupal-scaffold-excludes-omit-defaults": false,
    }
}
```

With `drupal-scaffold-excludes` you can provide additional paths, that should
not be copied or overwritten. Default excludes are provided by the plugin:
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

With setting `drupal-scaffold-excludes-omit-defaults` to `true`, those defaults
will be ignored.  Make sure you add relevant paths back to `drupal-scaffold-excludes`
manually, when enabling this setting.
