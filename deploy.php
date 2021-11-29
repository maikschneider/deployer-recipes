<?php

namespace Deployer;

require_once(__DIR__ . '/autoload.php');

set('repository', 'git@bitbucket.org:blueways/deployer-recipes.git');

host('local')
    ->hostname('local')
    ->set('deploy_path', getcwd())
    ->set('public_urls', ['https://deyploer-recipes.ddev.site']);

host('testing')
    ->hostname('p604424.mittwaldserver.info')
    ->stage('testing')
    ->user('p604424')
    ->set('http_user', 'p604424')
    ->set('writable_mode', 'chmod')
    ->set('bin/composer', '/usr/local/bin/composer')
    ->set('bin/php', '/usr/local/bin/php')
    ->set('deploy_path', '/home/www/p604424/html/typo3-test')
    ->set('public_urls', ['http://p604424.mittwaldserver.info']);

host('hidrive')
    ->hostname('blueways-kundenbackups@sftp.hidrive.strato.com')
    ->set('deploy_path', '/users/blueways-kundenbackups/Test')
    ->roles('backup_storage');

set('backup_storage_db_keep', 1);
