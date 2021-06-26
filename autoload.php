<?php

require_once(__DIR__ . '/vendor/sourcebroker/deployer-loader/autoload.php');
new \SourceBroker\DeployerExtendedTypo3\Loader();

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);
