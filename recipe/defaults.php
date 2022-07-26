<?php

namespace Deployer;

// Read database credentials from bin/typo3cms command. see: https://github.com/sourcebroker/deployer-extended-typo3/discussions/7
set('driver_typo3cms', true);

// always run language update
before('deploy:symlink', 'typo3cms:language:update');

// execute normal prepare before doing custom typo3 stuff
before('deploy:prepare:typo3', 'deploy:prepare');

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

task('deploy:check_branch_local', function () {
});

// fix permissions in xima server environment
set('writable_dirs', function () {
    return [
        get('web_path') . 'typo3conf',
        get('web_path') . 'typo3temp',
        get('web_path') . 'typo3temp/assets',
        get('web_path') . 'typo3temp/assets/images',
        get('web_path') . 'typo3temp/assets/_processed_',
        get('web_path') . 'uploads',
        get('web_path') . 'fileadmin',
        get('web_path') . '../var',
        get('web_path') . '../var/log',
        get('web_path') . '../var/transient',
        get('web_path') . 'fileadmin/_processed_',
    ];
});
task('deploy:createMissingFolderAndPermissions', function () {
    run('mkdir -p {{deploy_path}}/shared/public/fileadmin/_processed_');
    $remotePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
    run('chmod 660 ' . $remotePath . '/public/typo3conf/LocalConfiguration.php');
});
after('deploy:shared', 'deploy:createMissingFolderAndPermissions');