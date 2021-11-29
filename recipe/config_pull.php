<?php

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;

task('config:pull', function () {

    $sourceName = get('argument_stage');
    if (null === $sourceName) {
        throw new GracefulShutdownException("The source instance is required for config:pull command. [Error code: 1638190384]");
    }

    $remotePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');

    if (!test('[ -f ' . $remotePath . '/public/typo3conf/LocalConfiguration.php ]')) {
        throw new GracefulShutdownException("The source instance has no LocalConfiguration.php. [Error code: 1638191786]");
    }

    $localPath = !empty($_ENV['IS_DDEV_PROJECT']) ? '.' : get('deploy_path') . '/' . (testLocally('[ -e {{deploy_path}}/current ]') ? 'current' : '');

    runlocally('mkdir -p ' . $localPath . '/public');
    runlocally('mkdir -p ' . $localPath . '/public/typo3conf');
    download($remotePath . '/public/typo3conf/LocalConfiguration.php', $localPath . '/public/typo3conf');
})->desc('Synchronize LocalConfiguration.php from remote instance to local instance');
