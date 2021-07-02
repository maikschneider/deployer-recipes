<?php

namespace Deployer;

set('backup_storage_db_keep', get('backup_storage_db_keep', 10));

task('db:backup:rsync', function () {

    if (!empty(get('argument_stage'))) {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:backup:rsync ' . (input()->getOption('options') ? '--options=' . input()->getOption('options') : ''));
        return;
    }

    on(roles('backup_storage'), function ($host) {

        $server = $host;

        $host = $server->getRealHostname();
        $port = $server->getPort() ? ' -p' . $server->getPort() : '';
        $user = !$server->getUser() ? '' : $server->getUser() . '@';

        $include = '--include="*server=' . get('default_stage') . '*"';

        // sync db
        if (get('db_storage_path_local')) {
            runLocally('rsync ' . $include . ' -e \'ssh ' . $port . '\' {{db_storage_path_local}}/* ' . $user . $host . ':{{deploy_path}}/database/ --exclude="*.*"');
        }

        // prune
        $remoteFiles = runLocally('rsync -e \'ssh ' . $port . '\' ' . $user . $host . ':{{deploy_path}}/database/ | awk \'{ $1 = $2 = $3 = $4 = ""; print substr($0,5); }\'');
        $remoteFiles = preg_split("/\n/", $remoteFiles);
        $remoteFiles = array_filter($remoteFiles, function ($fileName) {
            return strpos($fileName, 'server=' . get('default_stage'));
        });

        // db
        $remoteDbs = array_filter($remoteFiles, function ($fileName) {
            return strpos($fileName, '.gz', -3);
        });
        $filesToDelete = array_splice($remoteDbs, 0, get('backup_storage_db_keep') * -2);

        // exit if nothing to delete
        if (!count($filesToDelete)) {
            return;
        }

        // workaround to delete files via rsync: create empty files locally, override storage files, clean dir, rsync back with --remove-source files
        $tempDir = '.dep/temp';
        runLocally('mkdir -p ' . $tempDir);
        runLocally('cd ' . $tempDir . ' && touch ' . implode(' ', $filesToDelete));
        runLocally('rsync -avP -e \'ssh ' . $port . '\' ' . $tempDir . '/* ' . $user . $host . ':{{deploy_path}}/database/');
        runLocally('rm -f ' . $tempDir . '/*');
        $include = implode('" --include="', $filesToDelete);
        runLocally('rsync -avP --include="' . $include . '" --exclude "*"  --remove-source-files -e \'ssh ' . $port . '\' ' . $user . $host . ':{{deploy_path}}/database/ ' . $tempDir . '/ ');
        runLocally('rm -rf ' . $tempDir);
    });
})->desc('Rsync database backups to remote host');
