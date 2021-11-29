<?php

namespace Deployer;

task('deploy:authorize:bitbucket', function () {

    // check stage
    if (empty(get('argument_stage'))) {
        writeln('<error>This task cannot be performed locally.</error>');
        return;
    }

    // check for ssh program + directory
    if (!test('[ -d ~/.ssh ]')) {
        writeln('no .ssh dir found, creating one..');
        run('mkdir -p ~/.ssh');
    }

    // check user authentication
    $localKey = runlocally('cat ~/.ssh/id_rsa.pub');
    if (!test('[[ $(grep "' . $localKey . '" ~/.ssh/authorized_keys) ]]')) {
        $doAuthorize = askConfirmation('Your ssh keys are not in ~/.ssh/authorized_keys. Do you want to authenticate yourself?',
            true);
        if ($doAuthorize) {
            writeln('Adding key..');
            run('echo "' . $localKey . '" >> ~/.ssh/authorized_keys');
        }
    }

    // check ssh keys
    if (!test('[ -f ~/.ssh/id_rsa.pub ]')) {
        writeln('no ssh keys found on host "{{hostname}}", creating keys..');
        run('ssh-keygen -b 2048 -t rsa -f ~/.ssh/id_rsa -q -N ""');
    }

    // check rsa fingerprint to repository
    $repoDomain = substr(substr(get('repository'), 4), 0, (strpos(get('repository'), ':') - 4));
    $repoIp = runlocally('ping -q -c 1 -t 1 ' . $repoDomain . ' | grep PING | sed -e "s/).*//" | sed -e "s/.*(//"');
    if (!test('[[ $(ssh-keygen -F ' . $repoIp . ') ]]')) {
        writeln($repoDomain . ' is not not a known_host, generating key locally and adding it to {{hostname}}..');
        $key = runLocally('ssh-keyscan -t rsa -H ' . $repoIp . '');
        run('echo "' . $key . '" >> ~/.ssh/known_hosts');
    }

    // check if git is installed
    $notInstalledProgramms = [];
    if (!test('[[ $(which git) ]]')) {
        $notInstalledProgramms[] = 'git';
    }

    // check if composer is installed
    if (!test('[[ $(which composer) ]]')) {
        $notInstalledProgramms[] = 'composer (version 2.x)';
    }

    // check composer version
    if (test('[[ $(which composer) ]]')) {
        $composerVersion = run('composer --version 2>/dev/null | egrep -o "([0-9]{1,}\.)+[0-9]{1,}"');
        $composerVersions = explode('.', $composerVersion);
        if ((int)$composerVersions[0] !== 2) {
            $notInstalledProgramms[] = 'composer (version 2.x, currently installed: ' . $composerVersion . ')';
        }
    }

    // abort if software is missing
    if (!empty($notInstalledProgramms)) {
        writeln('<error>The following programs are not installed: ' . implode($notInstalledProgramms) . '.</error>');
        writeln('<comment>Please install the programs before running a deployment</comment>');
    }

    // check for repository access
    if (test('[[ $(which git) ]]') && !test('git ls-remote -h {{repository}}')) {
        try {
            run('git ls-remote -h {{repository}}');
        } catch (\Deployer\Exception\RuntimeException $e) {
            $noAccess = strpos($e, 'Permission denied (publickey)');
            if ($noAccess) {
                writeln('<error>Error: "Permission denied (publickey)"</error>');
                writeln('<comment>Please grant "{{hostname}}" access to the git repository {{repository}} and re-run the command. This is the public key of the host:</comment>');
                writeln(run('cat ~/.ssh/id_rsa.pub'));
            } else {
                writeln('<error>Unknown error. Could not access {{repository}}.</error>');
            }
        }
    }

    // check for .env file
    if (!test('[ -f {{deploy_path}}/shared/.env ]')) {
        writeln('<comment>No .env file found in "{{deploy_path}}/shared/".</comment>');
        run('touch {{deploy_path}}/shared/.env');
        writeln('<info>.env file created</info>');
    }

    // check for instance
    if (!test('[[ $(grep "INSTANCE=" {{deploy_path}}/shared/.env) ]]')) {
        writeln('<comment>No Instance set in .env file.</comment>');
        run('echo "INSTANCE=\'' . get('argument_stage') . '\'" >> {{deploy_path}}/shared/.env');
        writeln('<info>Instance set to "{{argument_stage}}"</info>');
    }

    // check for TYPO3 context
    if (!test('[[ $(grep "TYPO3_CONTEXT=" {{deploy_path}}/shared/.env) ]]')) {
        writeln('<comment>No TYPO3_CONTEXT set in .env file. </comment>');
        $context = ask('Enter context name', 'Production');
        run('echo "TYPO3_CONTEXT=\'' . $context . '\'" >> {{deploy_path}}/shared/.env');
    }

    // check for database credentials
    if (!test('[[ $(grep "TYPO3_CONF_VARS__DB__Connections__Default__dbname=" {{deploy_path}}/shared/.env) ]]')) {
        writeln('<comment>No database credentials found .env file. </comment>');
        $askForDatabase = askConfirmation('Do you want to enter them now?', true);
        if ($askForDatabase) {
            $databaseName = ask('Database name:');
            $databaseHost = ask('Database host:', '');
            $databasePort = ask('Database port:', '3306');
            $databaseUser = ask('Database username:');
            $databasePassword = askHiddenResponse('Database password:');

            run('echo "" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3_CONF_VARS__DB__Connections__Default__host=\'' . $databaseHost . '\'" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3_CONF_VARS__DB__Connections__Default__port=\'' . $databasePort . '\'" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3_CONF_VARS__DB__Connections__Default__user=\'' . $databaseUser . '\'" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3_CONF_VARS__DB__Connections__Default__password=\'' . $databasePassword . '\'" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3_CONF_VARS__DB__Connections__Default__dbname=\'' . $databaseName . '\'" >> {{deploy_path}}/shared/.env');

            run('echo "" >> {{deploy_path}}/shared/.env');
            run('echo "# Copy configuration for deployer" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3__DB__Connections__Default__dbname=\$TYPO3_CONF_VARS__DB__Connections__Default__dbname" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3__DB__Connections__Default__host=\$TYPO3_CONF_VARS__DB__Connections__Default__host" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3__DB__Connections__Default__password=\$TYPO3_CONF_VARS__DB__Connections__Default__password" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3__DB__Connections__Default__port=\$TYPO3_CONF_VARS__DB__Connections__Default__port" >> {{deploy_path}}/shared/.env');
            run('echo "TYPO3__DB__Connections__Default__user=\$TYPO3_CONF_VARS__DB__Connections__Default__user" >> {{deploy_path}}/shared/.env');
        }
    }

    // check current symlink
    if (!test('[ -d {{deploy_path}}/current ]')) {
        run('mkdir -p {{deploy_path}}/releases');
        run('mkdir -p {{deploy_path}}/releases/1');
        run('mkdir -p {{deploy_path}}/releases/1/public');
        run('ln -sfn {{deploy_path}}/releases/1/public {{deploy_path}}/current');

        writeln('<info>Make sure ' . implode(',', get('public_urls')) . ' is pointing to {{deploy_path}}/current/public</info>');
    }
});
