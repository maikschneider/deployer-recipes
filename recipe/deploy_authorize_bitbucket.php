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

    // check bitbucket rsa access
    $repoDomain = substr(substr(get('repository'), 4), 0, (strpos(get('repository'), ':') - 4));
    $repoIp = runlocally('dig +short ' . $repoDomain);
    if (!test('[[ $(ssh-keygen -F ' . $repoIp . ') ]]')) {
        writeln('bitbucket.org is not not a known_host, generating key locally and adding it to {{hostname}}..');
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
        writeln('<comment>Please install the programs and re-run this command</comment>');
        return;
    }

    // check for repository access
    if (!test('git ls-remote -h {{repository}}')) {
        try {
            run('git ls-remote -h {{repository}}');
        } catch (\Deployer\Exception\RuntimeException $e) {
            $noAccess = strpos($e, 'Permission denied (publickey)');
            if ($noAccess) {
                writeln('<error>Error: "Permission denied (publickey)"</error>');
                writeln('<comment>Please grant "{{hostname}}" access to the git repository {{repository}} and re-run the command.</comment>');
                writeln('This is the public key of the host:');
                writeln(run('cat ~/.ssh/id_rsa.pub'));
                return;
            }

            writeln('Unkown error. Could not access {{repository}}. Aborting.');
        }
    }

});
