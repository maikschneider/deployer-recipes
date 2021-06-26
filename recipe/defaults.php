<?php

namespace Deployer;

set('web_path', get('web_path', 'public/'));

set('shared_files', get('shared_files',
    [
        '.env',
        'public/.htaccess',
        'public/typo3conf/LocalConfiguration.php',
        'public/typo3conf/AdditionalConfiguration.php',
        'public/typo3conf/PackageStates.php'
    ]));

// set default backup paths for media
set('file_backup_packages', get('file_backup_packages', [
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
]));