## blueways Deployer Recipes

This repository contains third party recipes that are build on top of [sourcebroker/deployer-extended-typo3](https://github.com/sourcebroker/deployer-extended-typo3). This package sets default deployer values for a common TYPO3 base extension installation.

This Readme contains examples for configuring a TYPO3 base extension for easy deployment.

## 1. Install

~~~sh
composer require blueways/deployer-recipes
~~~

## 2. Adjust composer.json

You have to add some lines to your ```composer.json``` in order to read the Conf vars on the fly and add the symlink to the extension on every deploy.

```json
  {
   "extra": {
      "helhum/dotenv-connector": {
         "env-file": ".env",
         "adapter": "Helhum\\DotEnvConnector\\Adapter\\SymfonyDotEnv"
      }
   },
   "scripts": {
      "typo3-cms-scripts": [
         "ln -sfn ../../../ public/typo3conf/ext/bw_myext"
      ],
      "post-autoload-dump": [
         "@typo3-cms-scripts"
      ]
   }
}
```

## 3. Create deploy.php

Create a ```deploy.php``` in your project root. This is a simple configuration file for deploying to Mittwald hosts:

```php
<?php

namespace Deployer;

require_once(__DIR__ . '/vendor/blueways/deployer-recipes/autoload.php');

set('repository', 'git@bitbucket.org:blueways/bw_myext.git');

host('staging')
    ->hostname('p590044.mittwaldserver.info')
    ->stage('staging')
    ->user('p590044')
    ->set('branch', 'master')
    ->set('public_urls', ['https://staging.myext.de'])
    ->set('http_user', 'p590044')
    ->set('writable_mode', 'chmod')
    ->set('bin/composer', '/usr/local/bin/composer')
    ->set('bin/php', '/usr/local/bin/php')
    ->set('deploy_path', '/home/www/p590044/html/typo3-staging');
```

## 4. Create AdditinalConfiguration.php

Add [this file](Documentation/AdditionalConfiguration.php) to `public/typo3conf/` in order to parse TYPO3_CONF_VARs from environment variables.

## 5. Create .env

Put this [.env](Documentation/.env) file in your project root. It contains typical ddev settings, that would have been set through `AdditionalConfiguration.php`. These settings override the production settings of TYPO3.

## 6. Add files to git

Files to put in git:

* ```public/.htaccess```
* ```public/typo3conf/LocalConfiguration.php``` (with production settings)
* ```.env``` (overrides production settings)
* ```public/typo3conf/AdditionalConfiguration.php``` (sets Conf vars from .env)
* ```public/typo3conf/PackageStates.php```

## 7. Run

Make sure to run the ```dep``` command from within the ddev container, e.g. add an alias to your ```~/.bashrc``` or ```~/.zshrc```:

```
alias dep="ddev exec vendor/bin/dep"
```

To create the folder structure on the remote host, run:

```
dep deploy:prepare staging
```

Now you can create your first deployment with

```
dep deploy staging
```

This will checkout the repository on the remote host and create a release folder. You need to adjust the database credentials. To do this, ssh to the host and replace the `.env file` with [this one](Documentation/.env.mittwald) (rename it).

```
dep ssh staging
# make sure, you are in the /release, /current or /shared dir
> vim .env
```
Exit and try again. Now there should be a `/current` symlink which points to the latest release. Add domains that point into the `/current/public` directory, make sure SSL is activated. Re-run the deploy command, now there should be no errors.

## Defaults

This package sets default values for various settings.

|Setting|Value
|-------|-----
|web_path|`public/`|
|file_backup_packages|`fileadmin`, `uploads` (excluding `_processed_` and `_temp_`)
|file_backup_keep|`1`

## Recipes

* `db:backup:rsync`
* `file:backup:rsync`

### db:backup:rsync

Rsync database backups to remote host.

### file:backup:rsync

Rsync file backups to remote host

## Settings

|Setting|Value
|---|---
|backup_storage_db_keep| `10`
|backup_storage_file_keep| `3`

## Examples

### Auto-Backup

1. Add a backup host in `deployer.php`: 

    ```php
    host('hidrive')
        ->hostname('username@hidrive.strato.com')
        ->set('deploy_path', '/home/backup/projectX')
        ->roles('backup_storage');
    ```

2. Add a custom pipeline in `bitbucket-pipeline.yml`:

    ```yaml
    definitions:
      steps:
        - step: &checkout-master
            name: Checkout master branch
            image: atlassian/default-image:2
            artifacts:
              - .git/**
            script:
              - git config remote.origin.fetch "+refs/heads/*:refs/remotes/origin/*"
              - git fetch origin
              - git checkout master
        - step: &install-deployer
            name: Install deployer
            image: composer:latest
            caches:
              - composer
            artifacts:
              - vendor/**
              - public/**
              - .env
            script:
              - composer install --no-ansi --no-interaction --no-progress --no-scripts --ignore-platform-reqs
              - mv .env.mittwald .env
    
    pipelines:
      custom:
        backup:
          - step: *checkout-master
          - step: *install-deployer
          - step:
              image: drud/ddev-webserver:v1.17.6
              name: Backup database and rsync to HiDrive
              script:
                - phpdismod xdebug
                - ./vendor/bin/dep db:backup production --no-interaction
                - ./vendor/bin/dep db:backup:rsync production --no-interaction
                - ./vendor/bin/dep file:backup production --no-interaction
                - ./vendor/bin/dep file:backup:rsync production --no-interaction
    ```
   
3. Register a new scheduler

    ![pipeline example](https://bitbucket.org/blueways/deployer-recipes/raw/master/Documentation/Images/bitbucket.png)
