<?php

namespace Deployer;

// Read database credentials from bin/typo3cms command. see: https://github.com/sourcebroker/deployer-extended-typo3/discussions/7
set('driver_typo3cms', true);

// always run language update
before('deploy:symlink', 'typo3cms:language:update');

// extend deploy:prepare to prepare remote host for typo3 install
after('deploy:prepare', 'deploy:authorize:bitbucket');

set('web_path', 'public/');

set('composer_channel', 2);

set('file_backup_packages', [
    'media' => [
        [
            '-path "./public/fileadmin/*"',
            '! -path "./public/fileadmin/_processed_/*"',
            '! -path "./public/fileadmin/_temp_/*"',
        ],
        [
            '-path "./public/uploads/*"',
        ]
    ]
]);

set('file_backup_keep', 1);

// slack config to notify only on production
task('slack:notify:success')->onStage('production');
task('slack:notify:failure')->onStage('production');
after('success', 'slack:notify:success');
after('deploy:failed', 'slack:notify:failure');

set('branch', 'master');

set('branch_detect_to_deploy', true);

set('db_dumpclean_keep', [
    'production' => 10,
    '*' => 2
]);

task('deploy:check_branch_local', function(){});
