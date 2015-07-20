<?php

if (php_sapi_name() != 'cli')
    exit("must be run from php-cli\n");

$log = 1;

define('PANEL_BASE_PATH', '/home/nulled/www');
set_include_path(get_include_path() . PATH_SEPARATOR . PANEL_BASE_PATH . '/server/modules/core/phpseclib');
require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Net/SSH2.php';

$ssh = new Net_SSH2('205.252.250.4');

if ( !$ssh->login('user', 'pass')) {
    exit('Login Failed');
}

$cmd_file = '/home/nulled/www/server/logs/command.issue';

unlink($cmd_file);

$i = 0;

while (true)
{
    if (is_file($cmd_file) AND filesize($cmd_file) AND ($command = file_get_contents($cmd_file)) !== false)
    {
        unlink($cmd_file);

        $i = 0;

        if ($log) echo "$cmd_file found\n";

        $return = $ssh->exec(trim($command));
        file_put_contents($cmd_file . '.receiving', $return);
        rename($cmd_file . '.receiving', $cmd_file . '.return');
    }

    $i++;
    if ($i > 10000)
    {
        $i--;
        // echo "usleep(200000)\n";
        usleep(200000);
    }
    else
        usleep(1000);
}

?>
