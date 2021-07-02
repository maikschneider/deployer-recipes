<?php

namespace Deployer;

set('web_path', 'public/');

set('shared_files', [
    '.env',
    'public/.htaccess',
    'public/typo3conf/LocalConfiguration.php',
    'public/typo3conf/AdditionalConfiguration.php',
    'public/typo3conf/PackageStates.php'
]);

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
