<?php

namespace Deployer;

task('deploy:authorize:bitbucket', function(){

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
    if (!test('[[ $(grep "'. $localKey.'" ~/.ssh/authorized_keys) ]]')) {
        $doAuthorize = askConfirmation('Your ssh keys are not in ~/.ssh/authorized_keys. Do you want to authenticate yourself?', true);
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
    if(!test('[[ $(ssh-keygen -F bitbucket.org) ]]')) {
        writeln('bitbucket.org is not not a known_host, generating key locally and adding it to {{hostname}}..');
        $key = runLocally('ssh-keyscan -t rsa -H bitbucket.org');
        run('echo "' . $key . '" >> ~/.ssh/known_hosts');
    }

    // check git access
    writeln('{{repository}}');


    //$key = runLocally('ssh-keygen -F bitbucket.org || ssh-keyscan -t rsa -H bitbucket.org');

    //test()

    //run('echo "' . $key . '" >> ~/.ssh/known_host');



});
