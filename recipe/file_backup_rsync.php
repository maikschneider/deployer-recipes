<?php

namespace Deployer;

set('backup_storage_media_keep', get('backup_storage_media_keep', 2));

task('file:backup:rsync', function () {

    if (!empty(get('argument_stage'))) {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} backups:rsync ' . (input()->getOption('options') ? '--options=' . input()->getOption('options') : ''));
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
            runLocally('rsync ' . $include . ' -e \'ssh ' . $port . '\' {{db_storage_path_local}}/* ' . $user . $host . ':{{deploy_path}}/ --exclude="*.*"');
        }
        // sync media
        if (get('file_backup_packages')) {
            runLocally('rsync -e \'ssh ' . $port . '\' {{file_backup_path}}/* ' . $user . $host . ':{{deploy_path}}/');
        }

        // prune
        $remoteFiles = runLocally('rsync -e \'ssh ' . $port . '\' ' . $user . $host . ':{{deploy_path}}/ | awk \'{ $1 = $2 = $3 = $4 = ""; print substr($0,5); }\'');
        $remoteFiles = preg_split("/\n/", $remoteFiles);
        $remoteFiles = array_filter($remoteFiles, function ($fileName) {
            return strpos($fileName, 'server=' . get('default_stage'));
        });

        // db
        $remoteDbs = array_filter($remoteFiles, function ($fileName) {
            return strpos($fileName, '.gz', -3);
        });
        $filesToDelete = array_splice($remoteDbs, 0, get('backup_storage_db_keep') * -2);

        // media
        $remoteMedias = array_filter($remoteFiles, function ($fileName) {
            return strpos($fileName, '.tar', -3);
        });
        $filesToDelete = array_merge($filesToDelete,
            array_splice($remoteMedias, 0, get('backup_storage_media_keep') * -1));

        // exit if nothing to delete
        if (!count($filesToDelete)) {
            return;
        }

        // workaround to delete files via rsync: create empty files locally, override storage files, clean dir, rsync back with --remove-source files
        $tempDir = !empty($_ENV['IS_DDEV_PROJECT']) ? '' : get('deploy_path') . '/';
        $tempDir .= '.dep/temp';
        runLocally('mkdir -p ' . $tempDir);
        runLocally('cd ' . $tempDir . ' && touch ' . implode(' ', $filesToDelete));
        runLocally('rsync -avP -e \'ssh ' . $port . '\' ' . $tempDir . '/* ' . $user . $host . ':{{deploy_path}}/');
        runLocally('rm -f ' . $tempDir . '/*');
        $include = implode('" --include="', $filesToDelete);
        runLocally('rsync -avP --include="' . $include . '" --exclude "*"  --remove-source-files -e \'ssh ' . $port . '\' ' . $user . $host . ':{{deploy_path}}/ ' . $tempDir . '/ ');
        runLocally('rm -rf ' . $tempDir);
    });
})->desc('Rsync database backups to remote host');