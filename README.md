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
      "method": "http",
      "source": "http://ftp.drupal.org/files/projects/drupal-{version}.tar.gz",
      "excludes": [
        "google123.html",
        "robots.txt"
      ],
      "settings": [
        "sites/default/example.settings.my.php"
      ],
      "omit-defaults": false
    }
  }
}
```
The `method` setting selects how the scaffold files will be downloaded.
Currently, `drush` and `http` are supported.  If the `http` method is selected,
then the `source` option may be used to specify the URL to download the
scaffold files from.  The default source is drupal.org.  The literal string
`{version}` in the `source` option is replaced with the current version of 
Drupal core being updated prior to download.

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

Usually, the `sites` folder should be excluded (this is the default); however,
there are some settings files, such as sites/default/default.settings.php,
which should be copied over.  Additional scaffold settings files may be
specified with the `settings` option. Default settings are provided by the
plugin:
```
sites/default/default.settings.php
sites/default/default.services.yml
sites/example.settings.local.php
sites/example.sites.php
```

When setting `omit-defaults` to `true`, neither the default excludes nor the
default setting will be provided; in this instance, only those files listed in 
`excludes` will be excluded, and only the files listed in `settings` will be
copied to sites/default. Make sure that the `excludes` option contains all 
relevant paths, as anything not listed here will be overwritten when using 
`omit-defaults`.

## Custom command

The plugin by default is only downloading the scaffold files when installing or
updating `drupal/core`. If you want to call it manually, you have to add the 
command callback to the `scripts`-section of your root `composer.json`, like this:

```json
{
  "scripts": {
    "drupal-scaffold": "DrupalComposer\\DrupalScaffold\\Plugin::scaffold"
  }
}
```

After that you can manually download the scaffold files according to your
configuration by using `composer drupal-scaffold`.
