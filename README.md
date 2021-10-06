## blueways Deployer Recipes

This repository contains third party recipes that are build on top of [sourcebroker/deployer-extended-typo3](https://github.com/sourcebroker/deployer-extended-typo3). This package sets default deployer values for a common TYPO3 base extension installation.

This Readme contains examples for configuring a TYPO3 base extension for easy deployment.

## Install

~~~sh
composer require blueways/deployer-recipes
~~~

Note: As long as the [pull request](https://github.com/sourcebroker/deployer-extended/pull/13) that fixes local backups is not merged, this package needs to use a fork of `sourcebroker/deployer-extended@^16.0`. Register the fork in your main `composer.json`:

```json
{
   "repositories": [
      {
         "type": "git",
         "url": "https://github.com/maikschneider/deployer-extended"
      }
   ],
   "require": {
      "blueways/deployer-recipes": "^1.0",
      "sourcebroker/deployer-extended": "dev-hotfix/local-backup as 16.1.0"
   }
}
```

## deploy.php

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

## composer.json

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

### Configuration files

Files to put in git:

* ```public/.htaccess```
* ```public/typo3conf/LocalConfiguration.php``` (with production settings)
* ```.env``` (overrides production settings)
* ```public/typo3conf/AdditionalConfiguration.php``` (sets Conf vars from .env)


#### .env

This `.env` file contains typical ddev settings, that would have been set through `AdditionalConfiguration.php`.

```yaml
TYPO3_CONTEXT='Development/Local'
INSTANCE='local'

TYPO3_CONF_VARS__DB__Connections__Default__dbname='db'
TYPO3_CONF_VARS__DB__Connections__Default__host='db'
TYPO3_CONF_VARS__DB__Connections__Default__password='db'
TYPO3_CONF_VARS__DB__Connections__Default__port='3306'
TYPO3_CONF_VARS__DB__Connections__Default__user='db'

TYPO3_CONF_VARS__BE__debug='true'
TYPO3_CONF_VARS__BE__compressionLevel=0

TYPO3_CONF_VARS__FE__debug='true'
TYPO3_CONF_VARS__FE__compressionLevel=0
TYPO3_CONF_VARS__FE__processor_path='/usr/bin'
TYPO3_CONF_VARS__FE__processor_path_lzw='/usr/bin'

TYPO3_CONF_VARS__MAIL__transport='smtp'
TYPO3_CONF_VARS__MAIL__transport_smtp_server='localhost:1025'
TYPO3_CONF_VARS__MAIL__transport_sendmail_command='/usr/local/bin/mailhog sendmail test@example.org --smtp-addr 127.0.0.1:1025'

TYPO3_CONF_VARS__GFX__processor='/usr/bin/'
TYPO3_CONF_VARS__GFX__processor='/usr/bin/'

TYPO3_CONF_VARS__SYS__trustedHostsPattern='.*.*'
TYPO3_CONF_VARS__SYS__devIPmask='*'
TYPO3_CONF_VARS__SYS__displayErrors=1
TYPO3_CONF_VARS__SYS__exceptionalErrors=12290
TYPO3_CONF_VARS__SYS__sitename='MY SITE DDEV'
```

#### AdditionalConfiguration.php

```php
<?php

/**
 * Parse environment variables into PHP global variables. Any '__' in a key will be interpreted as 'next array level'.
 * An example would be: TYPO3_CONF_VARS__DB__Connections__Default__dbname=some_db
 * Numeric values are converted to integers.
 *
 * @param array<string,string> $from the array which shall be watched for keys that are matching $allowedVariables
 * @param string[] $allowedVariables the names of variables in $GLOBALS that shall be imported
 */
function setGlobalsFromStrings(array $from, array $allowedVariables)
{
    foreach ($from as $k => $v) {
        $keyArr = explode('__', $k);
        if (in_array($variable = array_shift($keyArr), $allowedVariables)) {
            $finalKey = array_pop($keyArr);
            for ($level = &$GLOBALS[$variable]; $nextLevel = array_shift($keyArr);) {
                if (!isset($level[$nextLevel])) {
                    $level[$nextLevel] = [];
                }
                $level = &$level[$nextLevel];
            }
            if ($v === 'bool(false)') {
                $v = false;
            } elseif ($v === 'bool(true)') {
                $v = true;
            } elseif (is_numeric($v)) {
                $v = (int)$v;
            }

            $level[$finalKey] = $v;
        }
    }
}

/**
 * Sets environment variables from the shell environment of the user (e.g. used with docker --environment=), from
 * webserver's virtual host config, .htaccess (SetEnv), /etc/profile, .profile, .bashrc, ...
 * When a .env file and the composer package helhum/dotenv-connector is present, the values from .env are also present
 * in the environment at this stage.
 */
setGlobalsFromStrings($_SERVER, ['TYPO3_CONF_VARS']);

```


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
