<?php

// install deployer-extended-typo3
require_once(__DIR__ . '/vendor/sourcebroker/deployer-loader/autoload.php');
new \SourceBroker\DeployerExtendedTypo3\Loader();

// install own recipes
require_once(__DIR__ . '/recipe/defaults.php');
require_once(__DIR__ . '/recipe/db_backup_rsync.php');
require_once(__DIR__ . '/recipe/file_backup_rsync.php');
