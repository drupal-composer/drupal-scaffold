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
    "drupal-scaffold": {
      "excludes": [
        "google123.html",
        "robots.txt"
      ],
      "omit-defaults": false
    }
  }
}
```

With the `drupal-scaffold` option `excludes`, you can provide additional paths 
that should not be copied or overwritten. Default excludes are provided by the 
plugin:
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

When setting `omit-defaults` to `true`, the defaults excludes will not be
provided; in this instance, only those files listed in `excludes` will be
excluded.  Make sure that the `excludes` option contains all relevant paths,
as anything not listed here will be overwritten when using `omit-defaults`.
