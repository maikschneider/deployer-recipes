## blueways Deployer Recipes

This repository contains third party recipes that are build on top of [sourcebroker/deployer-extended-typo3](https://github.com/sourcebroker/deployer-extended-typo3).

## Install

~~~sh
composer require blueways/deployer-recipes
~~~

Include recipes in `deploy.php` file.

```php
require_once(__DIR__ . '/vendor/blueways/depyloer-recipes/autoload.php');
```

## Defaults

This package sets default values for various settings.

|Setting|Value
|-------|-----
|web_path|`public/`|
|shared_files|`.env`, `.htaccess`, `LocalConfiguration.php`, [AdditionalConfiguration.php](https://gist.github.com/jonaseberle/1ed3b12e645667f2e1228f091fcaaa20), `PackageStates.php`|
|file_backup_packages|`fileadmin`, uploads (excluding `_processed_` and `_temp_`)



## Recipes

* `db:backup:rsync`
* `file:backup:rsync`

### db:backup:rsync

Rsync database backups to remote host.

### file:backup:rsync

Rsync file backups to remote host

## Settings

* `backup_storage_db_keep`, default 5
* `backup_storage_file_keep`, default to 2

## Examples

```php
// set up backup storage
host('hidrive')
    ->hostname('username@hidrive.strato.com')
    ->set('deploy_path', '/users/username/MyProject')
    ->roles('backup_storage');
```
