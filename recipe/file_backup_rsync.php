<?php

namespace Deployer;

set('backup_storage_media_keep', get('backup_storage_media_keep', 3));

task('file:backup:rsync', function () {

    if (!empty(get('argument_stage'))) {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} file:backup:rsync ' . (input()->getOption('options') ? '--options=' . input()->getOption('options') : ''));
        return;
    }

    if (!get('file_backup_packages', false)) {
        return;
    }

    $file_backup_path = get('file_backup_path');

    on(roles('backup_storage'), function ($host) use ($file_backup_path) {

        $server = $host;

        $host = $server->getRealHostname();
        $port = $server->getPort() ? ' -p' . $server->getPort() : '';
        $user = !$server->getUser() ? '' : $server->getUser() . '@';

        // for every backup package
        foreach (get('file_backup_packages') as $packageName => $conf) {

            // sync backups
            runLocally('rsync -a --include \'' . $packageName . '/\' -e \'ssh ' . $port . '\' ' . $file_backup_path . '/ ' . $user . $host . ':{{deploy_path}}/backups/');

            // prune
            $remoteFiles = runLocally('rsync -a -e \'ssh ' . $port . '\' ' . $user . $host . ':{{deploy_path}}/backups/' . $packageName . '/ | awk \'{ $1 = $2 = $3 = $4 = ""; print substr($0,5); }\'');
            $remoteFiles = preg_split("/\n/", $remoteFiles);
            $remoteBackups = array_filter($remoteFiles, function ($fileName) {
                return strpos($fileName, '.tar.gz', -7);
            });
            $filesToDelete = array_splice($remoteBackups, 0, get('backup_storage_media_keep') * -1);

            // exit if nothing to delete
            if (!count($filesToDelete)) {
                return;
            }

            // workaround to delete files via rsync: create empty files locally, override storage files, clean dir, rsync back with --remove-source files
            $tempDir = '.dep/temp';
            runLocally('mkdir -p ' . $tempDir);
            runLocally('cd ' . $tempDir . ' && touch ' . implode(' ', $filesToDelete));
            runLocally('rsync -avP -e \'ssh ' . $port . '\' ' . $tempDir . '/* ' . $user . $host . ':{{deploy_path}}/backups/' . $packageName);
            runLocally('rm -f ' . $tempDir . '/*');
            $include = implode('" --include="', $filesToDelete);
            runLocally('rsync -avP --include="' . $include . '" --exclude "*"  --remove-source-files -e \'ssh ' . $port . '\' ' . $user . $host . ':{{deploy_path}}/backups/' . $packageName . '/ ' . $tempDir . '/ ');
            runLocally('rm -rf ' . $tempDir);
        };
    });
})->
desc('Rsync database backups to remote host');
