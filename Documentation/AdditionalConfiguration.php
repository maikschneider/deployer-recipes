<?php

/**
 * Parse environment variables into PHP global variables. Any '__' in a key will be interpreted as 'next array level'.
 * An example would be: TYPO3_CONF_VARS__DB__Connections__Default__dbname=some_db
 * Numeric values are converted to integers.
 *
 * @param array<string,string> $from the array which shall be watched for keys that are matching $allowedVariables
 * @param string[] $allowedVariables the names of variables in $GLOBALS that shall be imported
 */
function setGlobalsFromStrings(array $from, array $allowedVariables)
{
    foreach ($from as $k => $v) {
        $keyArr = explode('__', $k);
        if (in_array($variable = array_shift($keyArr), $allowedVariables)) {
            $finalKey = array_pop($keyArr);
            for ($level = &$GLOBALS[$variable]; $nextLevel = array_shift($keyArr);) {
                if (!isset($level[$nextLevel])) {
                    $level[$nextLevel] = [];
                }
                $level = &$level[$nextLevel];
            }
            if ($v === 'bool(false)') {
                $v = false;
            } elseif ($v === 'bool(true)') {
                $v = true;
            } elseif (is_numeric($v)) {
                $v = (int)$v;
            }

            $level[$finalKey] = $v;
        }
    }
}

/**
 * Sets environment variables from the shell environment of the user (e.g. used with docker --environment=), from
 * webserver's virtual host config, .htaccess (SetEnv), /etc/profile, .profile, .bashrc, ...
 * When a .env file and the composer package helhum/dotenv-connector is present, the values from .env are also present
 * in the environment at this stage.
 */
setGlobalsFromStrings($_SERVER, ['TYPO3_CONF_VARS']);
