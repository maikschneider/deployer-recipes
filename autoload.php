<?php

$vendorDir = substr(dirname(dirname(__DIR__)), -6, 6) === 'vendor' ? __DIR__ . '/../../' : __DIR__ . '/vendor/';

// install deployer-extended-typo3
require_once($vendorDir . 'sourcebroker/deployer-loader/autoload.php');
new \SourceBroker\DeployerExtendedTypo3\Loader();

// install recipe from old deployer/recipes
require_once(__DIR__ . '/recipe/slack.php');

// install own recipes
require_once(__DIR__ . '/recipe/deploy_prepare_typo3.php');
require_once(__DIR__ . '/recipe/defaults.php');
require_once(__DIR__ . '/recipe/db_backup_rsync.php');
require_once(__DIR__ . '/recipe/file_backup_rsync.php');
require_once(__DIR__ . '/recipe/file_backup.php');
require_once(__DIR__ . '/recipe/config_pull.php');
require_once(__DIR__ . '/recipe/deploy_fast.php');
