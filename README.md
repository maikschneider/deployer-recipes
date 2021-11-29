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

host('local')
    ->hostname('local')
    ->set('deploy_path', getcwd());
```

## 4. Create AdditinalConfiguration.php

Add [this file](Documentation/AdditionalConfiguration.php) to `public/typo3conf/` in order to parse TYPO3_CONF_VARs from environment variables.

## 5. Create .env

Put this [.env](Documentation/.env) file in your project root. It contains typical ddev settings, that would have been set through `AdditionalConfiguration.php`. These settings override the production settings of TYPO3.

## 6. Add files to git

Files to put in git:

* ```deploy.php```
* ```public/.htaccess```
* ```public/typo3conf/LocalConfiguration.php``` (with production settings)
* ```.env``` (overrides production settings)
* ```public/typo3conf/AdditionalConfiguration.php``` (sets Conf vars from .env)
* ```public/typo3conf/PackageStates.php``` (not needed in v11+)

Make sure that these files are not matched by your `.gitignore`. To add files that are excluded (like the ones inside the `public` directory), you can use the following command:

```
git add -f public/typo3conf/LocalConfiguration.php
```

## 6. (Optional) Configure your deployer command

Make sure to run the ```dep``` command from within the ddev container, e.g. add an alias to your ```~/.bashrc``` or ```~/.zshrc```:

```
alias dep="ddev exec vendor/bin/dep"
```

## 7. Prepare the host machine

Get ready to enter database credentials and configure the domain.

To create the folder structure and configuration on the remote host, run:

```
dep deploy:prepare:typo3 staging
```

Follow the wizard. If there are errors (e.g. missing software on host), fix the problems and re-run the command.

Now change the path of the domain (the ones configured in ```public_urls```) to the working directory. The path is printed by the wizard. Activate SSL.

## 8. Deploy

Now you can create your first deployment with

```
dep deploy-fast staging
```

## 9. Import database or start fresh

You're basically done. Now you can decide whether you want to import your existing local database:

```
dep db:push staging
```

Or start from scratch by adding an admin user:

```
config:createadmin staging
```

## 10. Configure remote instance

If you configure the remote TYPO3 installation via Install Tool, make sure to add the changes to the git repository. To download the changes made to the `LocalConfiguration.php`, you can use the following command:

```
dep config:pull staging
```

If any of these changes break your local installation, just override the setting in your local `.env` file.

---

## Defaults

This package sets default values for various settings.

|Setting|Value
|-------|-----
|web_path|`public/`|
|file_backup_packages|`fileadmin`, `uploads` (excluding `_processed_` and `_temp_`)
|file_backup_keep|`1`

---

## Recipes

* `db:backup:rsync`
* `file:backup:rsync`
* `config:pull`
* `slack:notify`
* `deploy:prepare:typo3`
* `deploy-fast`
* `file:backup`

### db:backup:rsync

Rsync database backups to remote host.

### file:backup:rsync

Rsync file backups to remote host

|Setting|Default value
|---|---
|backup_storage_db_keep| `10`
|backup_storage_file_keep| `3`

### config:pull

Download the `LocalConfiguration.php` from remote instance to local instance

### slack:notify

Notify slack channel on production deployment.

Contains multiple slack recipes in order to get rid of the abandoned repo (could change in the future). To add a webhook to your slack channel, visit [this page](https://deployer.org/docs/7.x/contrib/slack) and click on "Add to Slack".

### deploy:prepare:typo3

Configures the host machine for the deployment of a TYPO3 base extension:

* Can add your SSH key to remote machine
* Checks for software dependencies
* Add rsa fingerprint of git repository
* Checks for correct repository access
* Interactively create remote `.env` file with database credentials
* Creates needed folder structure for domain configuration

### deploy-fast

Unfortunately this package has to override the `deploy-fast` task of [sourcebroker/deployer-extended-typo3](https://github.com/sourcebroker/deployer-extended-typo3). This is done to change the order of commands in order to prevent an exception during initial deployment.

Hopefully, this will change in the future. The discussion can be found [here](https://github.com/sourcebroker/deployer-extended-typo3/discussions/18). 

### file:backup

This recipe overrides the `file:backup` command and the `file_backup_path` variable from [sourcebroker/deployer-extended](https://github.com/sourcebroker/deployer-extended) because it is not able to do create local backups, which is needed for our rsync.

The pull request for this change is [still open](https://github.com/sourcebroker/deployer-extended/pull/13).

---

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
