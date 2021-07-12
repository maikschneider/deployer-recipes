<?php

namespace Deployer;

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
task('slack:notify')->onStage('production');
task('slack:notify:failure')->onStage('production');
before('deploy', 'slack:notify');
after('success', 'slack:notify:success');
after('deploy:failed', 'slack:notify:failure');

set('branch', 'master');

set('branch_detect_to_deploy', true);

set('db_dumpclean_keep', [
    'production' => 10,
    '*' => 2
]);

task('deploy:check_branch_local', function(){});
