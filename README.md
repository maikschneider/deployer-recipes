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
    ->hostname('blueways-backups@sftp.hidrive.strato.com')
    ->set('deploy_path', '/users/blueways-backups/MyProject')
    ->roles('backup_storage');
```
