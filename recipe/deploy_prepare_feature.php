<?php

namespace Deployer;

task('deploy:prepare:feature', function () {

    // abort if feature branch has already been configured
    if (test('[ -f {{deploy_path}}/.dep/releases.extended ]')) {
        return;
    }

    $featureRootPath = get('deploy_path');
    $branch = get('branch');

    // create shared dir
    run('mkdir -p {{ deploy_path }}');
    run('mkdir -p {{ deploy_path }}/shared');

    // look for .env file
    if (test('[ ! -f ' . $featureRootPath . '/.env ]')) {
        throw new GracefulShutdownException('No .env file found in "' . $featureRootPath . '/"');
    }

    // copy .env file
    run('cp ' . $featureRootPath . '/.env {{deploy_path}}/shared/');

    // set database name
    $dbName = str_replace('-', '', $branch);
    run('echo "TYPO3_CONF_VARS__DB__Connections__Default__dbname=\'' . $dbName . '\'" >> {{deploy_path}}/shared/.env');

    // set TYPO3_BASE domain
    foreach (get('public_urls') as $url) {
        run('echo "TYPO3_BASE=\'' . $url . '\'" >> {{deploy_path}}/shared/.env');
    }

    // create empty database
    $dbUser = run('grep TYPO3_CONF_VARS__DB__Connections__Default__user {{deploy_path}}/shared/.env | cut -d "=" -f2 | cut -c2- | rev | cut -c2- | rev');
    $dbHost = run('grep TYPO3_CONF_VARS__DB__Connections__Default__host {{deploy_path}}/shared/.env | cut -d "=" -f2 | cut -c2- | rev | cut -c2- | rev');
    $dbPassword = run('grep TYPO3_CONF_VARS__DB__Connections__Default__password {{deploy_path}}/shared/.env | cut -d "=" -f2 | cut -c2- | rev | cut -c2- | rev');
    $sqlStatement = 'CREATE DATABASE IF NOT EXISTS ' . $dbName;
    run('echo "' . $sqlStatement . '" | mariadb -u ' . $dbUser . ' -h ' . $dbHost . ' --password="' . $dbPassword . '"');
})->onStage('feature');

task('db:import:feature', function () {

    // abort if feature branch has already been configured
    if (test('[ -f {{deploy_path}}/.dep/releases.extended ]')) {
        return;
    }

    // copy database from source
    $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
    run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:copy {{ db_source_host }} --options=target:feature');
})->onStage('feature');

task('override-paths', function () {
    $featureRootPath = get('deploy_path');
    $branch = get('branch');

    // override path & public url
    set('deploy_path', $featureRootPath . '/' . $branch);
    set('public_urls', array_map(function ($url) use ($branch) {
        return $url . '/' . $branch . '/current/public';
    }, get('public_urls')));
})->onStage('feature');

task('db:truncate', function () {
})->onStage('feature');

before('db:upload', 'override-paths');
before('db:import', 'override-paths');
before('db:rmdump', 'override-paths');
before('deploy:prepare', 'override-paths');
before('deploy:extend_log', 'db:import:feature');

before('deploy:prepare', 'deploy:prepare:feature');