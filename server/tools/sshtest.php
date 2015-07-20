#!/usr/bin/php -q
<?php

define('PANEL_BASE_PATH', '/home/nulled/www');

set_include_path(get_include_path() . PATH_SEPARATOR . PANEL_BASE_PATH . '/server/modules/core/phpseclib');

require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Net/SSH2.php';
require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Net/SFTP.php';
require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Crypt/RSA.php';
require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/File/ANSI.php';

class PHP_SSH
{
    protected $ssh, $time, $timestart, $uprompt, $rprompt, $host, $user, $pass, $timeout, $out, $prevtime, $cmd_file;
    public $logfile, $log2screen;

    public function __construct($host, $user, $pass, $timeout = 8)
    {
        $time = $this->timestart = $this->calc_time('Begin Program', 0.0);

        $this->host    = $host;
        $this->user    = $user;
        $this->pass    = $pass;
        $this->timeout = $timeout;
        $this->logfile = '/var/log/phpssh_log';
        $this->log2screen = false;
        $this->cmd_file = PANEL_BASE_PATH . '/server/logs/command.issue';

        $this->ssh = new Net_SSH2($this->host);

        if (! $this->ssh->login($this->user, $this->pass))
            exit('Login Failed' . "\n");

        $this->ssh->setTimeout($this->timeout);

        $this->time = $this->calc_time('logged in', $time);

        $this->init();

        $this->sudo();
    }

    protected function init()
    {
        $this->ssh->read('$$$');
        $this->ssh->write("\n");
        $this->uprompt = trim($this->ssh->read('$$$'));
        list($user, $host) = @preg_split('/[@:]/', $this->uprompt);
        $this->rprompt = "root@{$host}:~#";
        $this->time = $this->calc_time("prompts: {$this->uprompt} {$this->rprompt}", $this->time);
    }

    protected function sudo()
    {
        $this->ssh->write("sudo -i\n");
        $time = $this->calc_time('write sudo', $this->time);
        $out = trim($this->ssh->read('/.*[P|p]assword.*/', NET_SSH2_READ_REGEX));
        $time = $this->calc_time('read sudo: ' . $out, $time);

        if (preg_match('/.*[P|p]assword.*/', $out) != 0)
            $this->ssh->write($this->pass . "\n");
        else
            exit('SSH->sudo(): sudo password prompt not found: ' . $out . "\n");

        $out = trim($this->ssh->read($this->rprompt));
        $time = $this->calc_time('read sudo: ' . $out, $time);

        if (stristr($out, 'try again'))
            exit('SSH->sudo(): sudo password incorrect or user not in file: sudoers' . "\n");
        else
        {
            $this->ssh->write("whoami\n");
            $out = trim($this->ssh->read($this->rprompt));
            $time = $this->calc_time('read whoami: ' . $this->clean($out), $time);

            if ($this->out !== 'root')
                exit("whoami should be root but is: {$this->out}\n");

            $this->user = 'root';

            $this->time = $this->calc_time("You are now root.", $time);
        }
    }

    protected function clean($out)
    {
        list(, $out) = explode("\n", trim($out), 2);
        list(, $out) = explode("\n", strrev(trim($out)), 2);
        return $this->out = strrev(trim($out));
    }

    protected function calc_time($label, $prevtime)
    {
        $time = microtime(1);
        $logdata = $this->host . ': ' . substr((string)($time - $prevtime), 0, 4) . ': ' . $label . "\n";

        if ($this->log2screen)
            echo $logdata;

        if (is_file($this->logfile) AND $logdata)
            file_put_contents($this->logfile, $logdata, FILE_APPEND | LOCK_EX);


        return $time;
    }

    public function run_ssh($cmd, $mode = 'write')
    {
        $time   = microtime(1);
        $prompt = ($this->user == 'root') ? $this->rprompt : $this->uprompt;

        if ($mode == 'write')
            $this->ssh->write($cmd . "\n");
        else
            $out = trim($this->ssh->read($prompt));

        $logdata = $this->host . ': ' . substr((string)(microtime(1) - $time), 0, 4) . ': ' . trim("$mode $cmd") . "\n";

        if ($this->log2screen)
            echo $logdata;

        if (is_file($this->logfile) AND $logdata)
            file_put_contents($this->logfile, $logdata, FILE_APPEND | LOCK_EX);

        if ($mode == 'read')
            return $out;

        return $this->get_result($this->run_ssh($cmd, 'read'));
    }

    private function get_result($str)
    {
        @list(, $str) = @explode("\n", trim($str), 2);
        @list(, $str) = @explode("\n", strrev($str), 2);
        return @trim(@strrev($str));
    }

    public function loop()
    {
        file_put_contents($this->logfile, "$this->host: LOOP Started...\n", FILE_APPEND | LOCK_EX);

        $this->run_ssh('rm -f ' . $this->cmd_file . '*');

        $i = 0;

        while (true)
        {
            if (is_file($this->cmd_file) AND filesize($this->cmd_file) AND ($command = file_get_contents($this->cmd_file)) !== false)
            {
                unlink($this->cmd_file);

                $i = 0;

                file_put_contents($this->logfile, "{$this->host}: {$this->cmd_file} found\n", FILE_APPEND | LOCK_EX);

                $return = $this->run_ssh(trim($command));

                file_put_contents($this->cmd_file . '.receiving', $return);
                rename($this->cmd_file . '.receiving', $this->cmd_file . '.return');
            }

            $i++;
            if ($i > 10000)
            {
                $i--;
                usleep(100000);
            }
            else
                usleep(1000);
        }
    }

    public function __destruct()
    {
        $this->calc_time('END SCRIPT', $this->time);
        $this->ssh->disconnect();
    }
};

$ssh = new PHP_SSH('127.0.0.1', 'user', 'pass');

$ssh->loop();
//echo $ssh->run_ssh('whoami');

/*
 * put in ComonPanel.core, for now not using it ... experimental
final protected function RemoteDaemonExec($cmd) // always as root
{
    file_put_contents(PANEL_BASE_PATH . '/server/logs/command.issue.sending', $cmd);
    rename(PANEL_BASE_PATH . '/server/logs/command.issue.sending', PANEL_BASE_PATH . '/server/logs/command.issue');

    $i = 0;

    while (true)
    {
        if (is_file(PANEL_BASE_PATH . '/server/logs/command.issue.return'))
        {
            $return = file_get_contents(PANEL_BASE_PATH . '/server/logs/command.issue.return');
            unlink(PANEL_BASE_PATH . '/server/logs/command.issue.return');
            return $return;
        }
        usleep(1000);
        $i++;
        if ($i > 30000) // 30 second wait
            return '';
    }
}
*/

?>
