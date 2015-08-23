<?php

class PanelCommon extends MySQLi_Access
{
    private $menu = array(); // used in $this->MethodValid()
    private $ssh = null, $sudoOK = null, $ckey = null, $sshTimeout = 5;
    private $ubuntu_supported_versions = array('11.04', '11.10', '12.04', '12.10', '13.04', '13.10', '14.04', '14.10', '15.04', '15.10');

    protected $filter_colors = ' | sed -e "s/\x1b\[.\{1,5\}m//g"'; // https://unix.stackexchange.com/questions/14684/removing-control-chars-including-console-codes-colours-from-script-output?lq=1
    protected $tmp_prepend = 'panel_';

    private $RSA1 = '-----BEGIN RSA PRIVATE KEY-----';
    private $RSA2 = '-----END RSA PRIVATE KEY-----';

    protected $roles_allowed = array('admin', 'client', 'reseller');

    public $asset_random;

    protected function __construct($ckey)
    {
        if ($this->ckey === null)
            $this->ckey = $ckey;
    }

    final protected function GetAvailableModules()
    {
        return array('Nginx' => 'Nginx', 'Network' => 'Network', 'Postfix' => 'Postfix', 'Dovecot' => 'Dovecot');
    }

    final protected function GetSupportedDistros()
    {
        return $this->ubuntu_supported_versions;
    }

    final private function InitSSH()
    {
        if ($this->ssh == null)
        {
            if (! $this->IsIPAddress($_SESSION['host']))
                //return print_r($_SESSION, 1);
                return 'InitSSH: Must select a Server to manage';

            $this->ssh = new Net_SFTP($_SESSION['host']);

            $user = $pass = $privkey = $privpass = '';

            list($user, $pass, $privpass) = explode(' ', $this->Decrypt($_SESSION['cred']));

            //return "user:$user, pass:$pass";

            if (! $_SESSION['priv'])
            {
                if (! $this->ssh->login($user, $pass))
                    return 'InitSSH: Keyboard-Interactive Login Failed';
            }
            else // RSA KEY Login Method
            {
                $privkey = $this->Decrypt($_SESSION['priv']);

                if ($this->GetPrivateHostKeyType($privkey) != 'RSA')
                    return 'InitSSH: Private Host Key Login Failed, Key not RSA';
                else
                {
                    $key = new Crypt_RSA();
                    if ($privpass) $key->setPassword($privpass);
                    $key->loadKey($privkey);

                    if (! $this->ssh->login($user, $key))
                        return 'InitSSH: Private Host Key Login Failed';
                }
            }
        }
        else
        {
            $pubkey = $this->ssh->getServerPublicHostKey();
            if ($_SESSION['pubhost'] AND $pubkey !== $this->Decrypt($_SESSION['pubhost']))
                return 'InitSSH: Possible Man-in-the-Middle Attack!';
            else if (! $_SESSION['pubhost'])
                $_SESSION['pubhost'] = $this->Encrypt($pubkey);
        }

        // http://phpseclib.sourceforge.net/ssh/examples.html#interactive
        //$_SESSION['uprompt'] = $_SESSION['rprompt'] = '';
        $this->GetSSHPrompts($pass, false);

        return true;
    }

    final private function GetSSHPrompts($pass, $goSUDO = true)
    {
        //exit("{$_SESSION['uprompt']} AND {$_SESSION['rprompt']}");
        if ($_SESSION['uprompt'] AND $_SESSION['rprompt'])
            return;

        $this->ssh->enablePTY();
        $this->ssh->setTimeout($this->sshTimeout);

        $out = trim($this->SSHRead('$$$'));
        list($out) = explode("\n", strrev($out));
        list($user, $host) = preg_split('/[@:]/', trim(strrev($out)));
        $host = trim($host);
        if (preg_match('/[:; ]/', $host))
        {
            $_SESSION['uprompt'] = $_SESSION['rprompt'] = '';
            exit('PanelCommon->GetSSHPrompts: Invalid characters in user prompt : ' . $host);
        }
        $_SESSION['uprompt'] = trim(strrev($out));
        $_SESSION['rprompt'] = "root@{$host}:~#";

        if ($goSUDO)
            $this->GoSUDO($pass);
    }

    final private function GoSUDO($pass)
    {
        if ($this->sudoOK) return;

        $uprompt = $_SESSION['uprompt'] ? $_SESSION['uprompt'] : '$$$';
        $rprompt = $_SESSION['rprompt'] ? $_SESSION['rprompt'] : '$$$';

        if ($uprompt === '$$$' OR $rprompt === '$$$')
        {
            $this->ssh->enablePTY();
            $this->ssh->setTimeout($this->sshTimeout);
        }

        $this->ssh->write("sudo -s\n");
        $out = $this->SSHRead('/.*[P|p]assword.*/', 1);

        if (preg_match('/.*[P|p]assword.*/', $out))
            $this->ssh->write($pass . "\n");
        else
        {
            //$_SESSION['uprompt'] = $_SESSION['rprompt'] = '';
            exit('PanelCommon->GoSUDO: Unable to get sudo password prompt: ' . $out);
        }

        $out = $this->SSHRead($rprompt);

        if (stristr($out, 'try again'))
        {
            //$_SESSION['uprompt'] = $_SESSION['rprompt'] = '';
            exit('PanelCommon->GoSUDO: sudo password incorrect or user not in file: sudoers');
        }
        else
        {
            if ($rprompt == '$$$')
                $_SESSION['rprompt'] = $rprompt = trim($out);

            $this->ssh->write("whoami\n");

            $out = $this->SSHRead($rprompt, 0, 1);

            if ($out !== 'root')
            {
                //$_SESSION['uprompt'] = $_SESSION['rprompt'] = '';
                exit('PanelCommon->GoSUDO: whoami not root, returned: '. $out);
            }

            $this->sudoOK = 1;
        }
    }

    final private function SSHRead($in, $regex = 0, $cleanoutput = 0)
    {
        $s_time = microtime(1);
        $out = ($regex) ? $this->ssh->read($in, NET_SSH2_READ_REGEX) : $this->ssh->read($in);
        $time = substr(microtime(1)-$s_time, 0, 4);

        if ($out)
        {
            $fout = "$time: $in: $out\n";
            file_put_contents(PANEL_BASE_PATH . '/server/logs/timeouts' . $_SESSION['host'], $fout, FILE_APPEND | LOCK_EX);

            $fout = "ssh->read() timed out\n";
            if ($this->ssh->isTimeout())
                file_put_contents(PANEL_BASE_PATH . '/server/logs/timeouts' . $_SESSION['host'], $fout, FILE_APPEND | LOCK_EX);
        }

        return ($cleanoutput) ? $this->SSHClean($out) : $out;
    }

    final private function SSHClean($str)
    {
        list(, $str) = explode("\n", trim($str), 2);
        list(, $str) = explode("\n", strrev($str), 2);

        return trim(strrev($str));
    }

    final protected function RemoteExec($cmd, $rand = null, $method = null)
    {
        if (($result = $this->InitSSH()) !== true)
        {
            $this->SessionSwap('previous');
            return $result;
            //trigger_error($result);
        }

        if (strstr($cmd, 'sudo '))
            exit('ERROR: RemoteExec() remove sudo in commands, we are already root');

        if (! $this->sudoOK)
        {
            list($user, $pass) = explode(' ', $this->Decrypt($_SESSION['cred']));
            $this->GoSUDO($pass);
        }

        $is_ansi = ($cmd AND in_array($cmd, array('top')) AND $this->sudoOK) ? 1 : 0;
        $polling = ($rand AND ctype_alnum($rand) AND in_array($method, array('nohup','pscreen'))) ? 1 : 0;

        if ($polling)
        {
            // no need to redirect stderr to stdout, nohup does this for you
            //$randcapture = ' >/tmp/' . $randfile . ' &';
            // bashpre-4 more compatible
            //$randcapture = ' >/tmp/' . $randfile . ' 2>&1 &';
            // bash4 is the new way, but less compatible
            //$randcapture = ' &>/tmp/' . $randfile;
            $log = '/tmp/' . $this->tmp_prepend . $rand;
            $cnf = '/root/.screenrc_panel';

            if ($method == 'nohup')
            {
                $this->ssh->write("touch {$log}; nohup {$cmd} >{$log} &\n");
                $out = $this->SSHRead($_SESSION['rprompt'], 0, 1);

                list(, $pid) = explode(' ', $out);

                if ($pid AND is_numeric($pid))
                    $this->ssh->write("echo '$pid' > {$log}.pid\n");
                else
                    exit('PanelCommon->RemoteExec(): method: nohup, non-numeric pid: ' . $pid);

                $this->SSHRead($_SESSION['rprompt']);

                return $pid;
            }
            else if ($method == 'pscreen')
            {
                $this->ssh->write("screen -wipe; screen -li\n");
                $out = $this->SSHRead($_SESSION['rprompt'], 0, 1);

                if (stristr($out, 'no sockets found'))
                {
                    $write_cnf = "echo 'log on' > {$cnf}; echo 'logfile {$log}' >> {$cnf}; echo 'logfile flush 5' >> {$cnf}";
                    $this->ssh->write("{$write_cnf}; screen -c {$cnf} -dmLS panel\n");
                    $out = $this->SSHRead($_SESSION['rprompt']);

                    $this->ssh->write("screen -li | grep panel | cut -d'.' -f1\n");
                    $pid = $this->SSHRead($_SESSION['rprompt'], 0, 1);

                    if ($pid AND is_numeric($pid))
                        $this->ssh->write("echo '$pid' > {$log}.pid\n");
                    else
                        exit('PanelCommon->RemoteExec(): method: pscreen, non-numeric pid: ' . $pid);

                    $this->SSHRead($_SESSION['rprompt']);
                    $_SESSION['pscreen'] = $_SESSION['pscreen2'] = $rand;

                    return $pid;
                }
                else
                {
                    // get pid of running screen named 'panel'
                    // $screenPID = 'screen -li | grep panel | sed -e "s/^[ \t]*//" | cut -d"." -f1';
                    // get config file of screen with specified pid
                    // $screenCONF = 'ps aux | grep screen | grep $('.$screenPID.') | tr -s " " | cut -d" " -f13';
                    // get $rand from logfile
                    // $screenRAND = 'cat $('.$screenCONF.') | grep "logfile /" | cut -d"/" -f3 | | cut -d"_" -f2';
                    // below is on long command to do the above action, you have to use bash vars, you cannot nest $() like you would think
                    $cmd_get_rand = 'pid=$(screen -li | grep panel | sed -e "s/^[ \t]*//" | cut -d"." -f1); conf=$(ps aux | grep screen | grep $pid | tr -s " " | cut -d" " -f13); echo $(cat $conf | grep "logfile /" | cut -d"/" -f3  | cut -d"_" -f2)';

                    //if (! $_SESSION['pscreen'] AND $_SESSION['pscreen2']) $_SESSION['pscreen'] = $_SESSION['pscreen2'];
                    //else if (! $_SESSION['pscreen2'])
                    //{
                        //$this->ssh->write("cat {$c} | grep 'logfile /' | cut -d'/' -f3 | | cut -d'_' -f2\n");
                        $this->ssh->write($cmd_get_rand . "\n");
                        $out = $this->SSHRead($_SESSION['rprompt'], 0, 1);
                        //exit($out);
                        $_SESSION['pscreen'] = $_SESSION['pscreen2'] = $out;
                    //}

                    $cmd1 = $cmd2 = '';
                    if (strstr($cmd, '----')) list($cmd1, $cmd2) = explode('----', $cmd, 2);

                    $cmd_do_rtn = 'screen -S panel -p 0 -X stuff "'.$cmd.'$(echo -ne \'\015\')"';
                    $cmd_no_rtn = 'screen -S panel -p 0 -X stuff "'.$cmd.'"';

                    $cmd = ($cmd2 === 'noenterkey') ? $cmd_no_rtn : $cmd_do_rtn;

                    $this->ssh->write($cmd . "\n");
                    $this->SSHRead($_SESSION['rprompt']);

                    return 'send: ' . $cmd;
                }
            }
        }
        else
        {
            if (strstr($cmd, 'ls '))
                $this->ssh->write("{$cmd}{$this->filter_colors}\n");
            else
                $this->ssh->write("{$cmd}\n");

            if ($method === 'pscreen')
            {
                $this->ssh->write("echo -n '{$_SESSION['pscreen']}'\n");

                $out = $this->SSHRead($_SESSION['pscreen'].$_SESSION['rprompt'], 0, 1);
                $out = $this->MakeSingleSpace($out);
                $out = str_replace("{$_SESSION['rprompt']} echo -n '{$_SESSION['pscreen']}'", '', $out);

                return trim($out);
            }
        }

        $out = $this->SSHRead($_SESSION['rprompt']);

        if ($is_ansi)
        {
            $ansi = new File_ANSI();

            if ($cmd === 'top')
            {
                $this->ssh->write('u');
                $this->ssh->write("$user\n");
                $out = $this->SSHRead($_SESSION['rprompt']);
                $ansi->appendString(trim($out));
                $out = htmlspecialchars_decode(strip_tags($ansi->getScreen()));
                $this->ssh->write('q');
                $this->SSHRead($_SESSION['rprompt']);
            }

            return $this->SSHClean($out);
        }
        else
        {
            $out = $this->SSHClean($out);
            return $out;
        }
    }

    final protected function GetPrivateHostKeyType($privkey)
    {
        $RSA1 = substr($privkey, 0, strlen($this->RSA1));
        $RSA2 = substr($privkey, strlen($privkey)-strlen($this->RSA2));

        return ($RSA1 == $this->RSA1 AND $RSA2 == $this->RSA2) ? 'RSA' : '';
    }

    /*
    put('filename.remote', 'xxx');
    put('filename.remote', 'filename.local', NET_SFTP_LOCAL_FILE);
    get('filename.remote');
    get('filename.remote', 'filename.local');
    mkdir('test');
    chdir('test');
    pwd();
    rmdir('test');
    delete('test', true);
    rename('filename.remote', 'newname.remote');
    nlist('.')
    NET_SFTP_TYPE_REGULAR
    NET_SFTP_TYPE_DIRECTORY
    NET_SFTP_TYPE_SYMLINK
    NET_SFTP_TYPE_SPECIAL
    rawlist('.')
    chmod(0777, 'dirname.remote', true);
    touch('filename.remote');
    chown('filename.remote', $uid, true);
    chgrp('filename.remote', $gid, true);
    truncate('filename.remote', $size);
    size('filename.remote');
    stat('filename.remote'));
    lstat('filename.remote'));
    */
    final protected function SFTP($f = '', $p = array())
    {
        if (! is_array($p))
            exit('ERROR: PanelCommon->SFTP() 2nd arg must be array');

        // 'put','get','mkdir','chdir','pwd','rmdir','delete','rename','nlist','rawlist',
        // 'chmod','touch','chown','chgrp','truncate','size','stat','lstat'
        // not all functions are useful, since we can not be root to use them
        // ex: upload into root owned dir: $this->SFTP('put', array('/tmp/xxdx', $str)); $this->RemoteExec("mv /tmp/xxdx /root/whatever", 1);

        if (! in_array($f, array('put','get','nlist','rawlist')))
            exit('ERROR: PanelCommon->SFTP('.$f.') function not allowed');

        if (($result = $this->InitSSH()) !== true)
            exit($result);

        $$func = $f;

        //$this->sudoOK = 0;

        if (count($p) == 0) return $this->ssh->$$func();
        if (count($p) == 1) return $this->ssh->$$func($p[0]);
        if (count($p) == 2) return $this->ssh->$$func($p[0], $p[1]);
        if (count($p) == 3) return $this->ssh->$$func($p[0], $p[1], $p[2]);

        exit('ERROR: PanelCommon->SFTP() input params wrong number');
    }

    final protected function RunMethod($module, $method, $ckey)
    {
        if ($this->ckey === null)
            $this->ckey = $ckey;

        $class = new ReflectionClass($module);

        ($class->hasMethod($method) AND $this->MethodValid($class->getMethod($method))) ? $this->$method() : $this->home();
    }

    final protected function MethodValid($m)
    {
        if (! ($m instanceof ReflectionMethod) )
            exit("ERROR: PanelCommon::MethodValid() not instanceof 'ReflectionMethod'");

        $method = '';

        foreach ($this->menu as $key => $val)
        {
            list($method) = explode(':', $val);
            if ($method == $m->name) break;
            $method = '';
        }

        $valid = ($m->isFinal() AND
                  $m->isPublic() AND
                  $method AND
                ! $m->isPrivate() AND
                ! $m->isStatic() AND
                ! $m->isAbstract() AND
                ! $m->isConstructor() AND
                ! $m->isDestructor()) ? true : false;

        return $valid;
    }

    final protected function IsShellSafe($in)
    {
        return (preg_match('/^[a-z0-9_\/\+\-\.\[]+$/i', $in)) ? true : false;
    }

    final protected function IsIPAddress($ip)
    {
        if ($ip == 'localhost')
          $ip = '127.0.0.1';

        return (ip2long($ip) !== false) ? true : false;
    }

    final protected function IsLinuxAccount($in)
    {
        // http://gskinner.com/RegExr/
        return (preg_match('/^[a-z][-a-z0-9]{1,7}$/', $in)) ? true : false;
    }

    final protected function IsFileThere($in, $op = '-e')
    {
        // -e/exists -d/directory -f/file -h/symlink -r/readable -w/writable -x/executable -S/socket -s/size not 0;
        $out = $this->RemoteExec("test $op '$in' && echo 'yes exists'");
        return (strpos($out, 'yes exists') !== false) ? true : false;
    }

    final protected function IsLinuxPassword($in)
    {
        return (preg_match('/^[^~`\s]{6,16}$/', $in)) ? true : false;
    }

    final protected function IsEmail($in)
    {
        return (preg_match('/^([a-z0-9+_]|\-|\.)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/i', $in)) ? true : false;
    }

    final protected function IsDomain($in)
    {
        return (preg_match('/^(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/i', $in)) ? true : false;
    }

    final protected function Request($request, $sanitize = '', $method = '')
    {
        if (strtoupper($method) === 'GET')
            $req = (@$_GET[$request])  ? $_GET[$request]  : '';
        else if (strtoupper($method) === 'POST')
            $req = (@$_POST[$request]) ? $_POST[$request] : '';
        else
        {
            if (@$_GET[$request])
                $req = $_GET[$request];
            else if (@$_POST[$request])
                $req = $_POST[$request];
            else
                $req = '';
        }

        if ($req AND ! is_array($req))
        {
            if (! is_array($sanitize) AND strstr($sanitize, ' '))
                $sanitize = explode(' ', $this->PackWhiteSpace($sanitize));
            else
                $sanitize = array($sanitize);

            foreach ($sanitize as $opt)
            {
                switch ($opt)
                {
                    case 'trim':         $req = trim($req);           break;
                    case 'addslashes':   $req = addslashes($req);     break;
                    case 'escshell':     $req = escapeshellcmd($req); break;
                    case 'lcase':        $req = strtolower($req);     break;
                    case 'ucase':        $req = strtoupper($req);     break;
                    case 'rawurldecode': $req = rawurldecode($req);   break;
                    case 'urldecode':    $req = urldecode($req);      break;
                    default: break;
                }
            }
        }

        return $req;
    }

    final protected function SetValue($haystack, $needle, $value, $delim = '=', $skip = array())
    {
        $found = false;
        $lines = explode("\n", $haystack);
        $num = count($lines);

        if (is_string($skip))
            $skip = array($skip);

        $is_comments = (is_array($skip) AND count($skip)) ? true : false;

        for ($i=0; $i < $num; $i++)
        {
            if ($is_comments)
                foreach ($skip as $toskip)
                    if (substr(trim($lines[$i]), 0, strlen($toskip)) == $toskip)
                        continue 2;

            if (strstr($lines[$i], $needle) AND ! $found)
            {
                list($lval) = preg_split("/[" . $delim . "]+/", $lines[$i]);
                $lval = trim($lval);
                if ($lval != $needle) continue;
                $lines[$i] = $lval . $delim . $value;
                $found = true;
            }
        }

        return ($found) ? implode("\n", $lines) : false;
    }

    final protected function GetValue($haystack, $needle, $delim = '=', $skip = array())
    {
        $lines = explode("\n", $haystack);

        if (is_string($skip))
            $skip = array($skip);

        $is_comments = (is_array($skip) AND count($skip)) ? true : false;

        foreach ($lines as $line)
        {
            $line = trim($line);

            if ($is_comments)
            {
                foreach ($skip as $toskip)
                {
                    if (substr($line, 0, strlen($toskip)) == $toskip)
                        continue 2;
                }
            }

            if (strstr($line, $needle))
            {
                list(, $value) = preg_split("/[" . $delim . "]+/", $line);
                return trim($value);
            }
        }

        return false;
    }

    final protected function IsDistroSupported($distro)
    {
        return (strtolower($distro[0]) === 'ubuntu' AND in_array($distro[1], $this->GetSupportedDistros())) ? true : false;
    }

    final protected function GetDistroType($asRoot = false)
    {
        $distro = array();

        // cat /etc/*{release,version}

        if (! $this->IsFileThere('/etc/issue'))
            return false;

        $out = trim($this->PackWhiteSpace($this->RemoteExec('cat /etc/issue', $asRoot)));

        foreach (explode("\n", trim($out)) as $line)
        {
            list($p1, $p2) = explode(' ', $this->PackWhiteSpace(trim($line)));

            $distro[0] = ucfirst(strtolower($p1));
            $distro[1] = trim($p2);

            if (ctype_alpha($distro[0]) AND preg_match('/^(\d\d\.\d\d|\d\d\.\d\d\.\d)$/', $distro[1]))
                return $distro;
        }

        return false;
    }

    final protected function IsPathJailed($path, $jail_path)
    {
        if (is_string($jail_path) AND is_string($path))
        {
            $path_check = substr($path, 0, strlen($jail_path));
            return ($path_check == $jail_path) ? true : false;
        }

        return false;
    }

    final protected function IsMarked($item1, $item2, $type = 'checked')
    {
        $mark = ($type === 'checked') ? ' checked="checked" ' : ' selected="selected" ';
        return ($item1 == $item2) ? $mark : ' ';
    }

    final protected function Page($page)
    {
        if (strstr($page, '::'))
            $page = str_replace('::', '/', $page);

        return PANEL_BASE_PATH . '/server/phtml/' . $page . '.php';
    }

    final protected function Encrypt($data)
    {
        if (! is_string($data) OR $data === '')
            exit('Encrypt: no input given');

        $ckey = $this->GetCKey();

        if (! is_string($ckey) OR strlen($ckey) != 24)
            exit('Encrypt: no key given');

        $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
        $cipher->setKey($ckey);
        $return = $cipher->encrypt($data);
        unset($cipher);

        return base64_encode($return);
    }

    final protected function Decrypt($data)
    {
        if (! is_string($data) OR $data === '')
            exit('Decrypt: no input given');

        $ckey = $this->GetCKey();

        if (! is_string($ckey) OR strlen($ckey) != 24)
            exit('Decrypt: no key given');

        $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
        $cipher->setKey($ckey);
        $return = $cipher->decrypt(base64_decode($data));
        unset($cipher);

        return $return;
    }

    final protected function PackWhiteSpace($in)
    {
        // tr -s ' '
        return preg_replace('/\h+/', ' ', trim($in));
    }

    final protected function MakeSingleSpace($in)
    {
        return preg_replace('/\v{2,}/', "\n", trim($in));
    }

    final protected function RemoveCtrlChars($in)
    {
        return preg_replace('/[[:cntrl:]]/', '', trim($in));
    }

    final protected function RandomString($length = 8)
    {
        $c = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($s = '', $cl = strlen($c)-1, $i = 0; $i < $length; $s .= $c[mt_rand(0, $cl)], ++$i);
        return $s;
        //return substr(base_convert(bin2hex(openssl_random_pseudo_bytes(32)), 16, 36), 0, $length);
    }

    final protected function SubmitStatus($in)
    {
        if ($in)
        {
            $css = (stristr($in, 'ERROR')) ? 'submit_status_error' : 'submit_status_noerror';
            return '<div id="notValid" class="submit_status ' . $css . '">' . $in . '</div>' . "\n";
        }

        return '';
    }

    final protected function MakeSingleSelect($name, $options, $selected = '')
    {
        $select = '<select name="' . $name . '">' . "\n";

        foreach ($options as $key => $value)
        {
            $select .= '<option value="' . $value . '"';
            if ($value == $selected) $select .= ' selected="selected"';
            $select .= '> ' . $key . ' </option>' . "\n";
        }

        return $select . '</select>' . "\n";
    }

    final protected function MooToolsMultiSelect($name, $options, $checked = array(), $onclick = '')
    {
        if (! is_array($checked)) $checked = array();

        $html = '<div class="MultiSelect">'."\n";

        $i = 1;
        foreach ($options as $n => $v)
        {
            $chk = (in_array($v, $checked)) ? ' checked="checked"' : '';
            $html .= '<input id="box'.$i.'" name="'.$name.'[]" value="'.$v.'" '.$chk.' type="checkbox"> <label for="box'.$i.'">'.$n.'</label>'."\n";
            $i++;
        }

        return $html .= '</div>'."\n";
    }

    final protected function Submitted($type, $demoAllowed = false)
    {
        return ($_SESSION['username'] == 'demo' AND ! $demoAllowed) ? false : $type;
    }

    final protected function BuildMenu($title, $menu, $denyDemo = '')
    {
        $this->menu    = $menu;
        $_menu[$title] = $this->menu;

        if (! $denyDemo)
            return $_menu;

        $notAllowed = explode(' ', $this->MakeSingleSpace($denyDemo));
        if (! $_SESSION['host']) $notAllowed = array('software', 'hardware', 'terminal');

        $newMenu = $_newMenu = array();

        foreach ($menu as $key => $val)
        {
            list($method) = explode(':', $val);

            if (! in_array($method, $notAllowed))
                $newMenu = array_merge($newMenu, array($key => $val));
        }

        $_newMenu[$title] = $newMenu;
        $this->menu       = $newMenu;

        return $_newMenu;
/*
        $_menu = $newMenu = array();

        $this->menu    = $menu;
        $_menu[$title] = $this->menu;

        // filter menu
        // if denyDemo and demo mode
        // if no host selected
        if (($_SESSION['username'] == 'demo' AND $denyDemo) OR ! $_SESSION['host'])
        {
            $notAllowed = ($_SESSION['host'] AND $denyDemo) ?
                explode(' ', $this->MakeSingleSpace($denyDemo)) :
                array('software', 'hardware', 'terminal');

            foreach ($menu as $key => $val)
            {
                list($method) = explode(':', $val);

                if (! in_array($method, $notAllowed))
                    $newMenu = array_merge($newMenu, array($key => $val));
            }
        }

        $this->menu    = $newMenu;
        $_menu[$title] = $this->menu;

        return $_menu;
 */
    }

    final protected function Instantiate($classname)
    {
        $classname = ucfirst(strtolower(trim($classname)));

        $classfile = PANEL_BASE_PATH . '/server/modules/comp/' . $classname . '.class.php';

        if (is_file($classfile))
            require_once $classfile;

        if (! class_exists($classname, false))
            exit('ERROR: ' . $classname . ' is not defined as a Class');

        $class = new ReflectionClass($classname);

        if (! $class->isSubclassOf('PanelCommon'))
            exit("ERROR: {$classname} must extends PanelCommon");

        if (! $class->isUserDefined())
            exit("ERROR: {$classname} must be user defined and not internal to PHP");

        if (! $class->IsInstantiable())
            exit("ERROR: {$classname} must be instantiable and not an Interface or Abstract class");

        if (! $class->hasMethod('home'))
            exit("ERROR: {$classname} lacks required method/function home()");

        if (! $class->hasProperty('menu'))
            exit("ERROR: {$classname} lacks \$menu as a class property");

        return new $classname;
    }

    final protected function SessionSwap($type)
    {
        if (! in_array($type, array('current', 'previous')))
            exit('PanelCommon->SessionSwap(): invalid type given');

        if ($_SESSION['prev_host'] AND $type == 'previous')
        {
            $_SESSION['host'] = $_SESSION['prev_host'];
            $_SESSION['cred'] = $_SESSION['prev_cred'];
            $_SESSION['priv'] = $_SESSION['prev_priv'];
            unset($_SESSION['prev_host'], $_SESSION['prev_cred'], $_SESSION['prev_priv']);

            return true;
        }
        else if ($_SESSION['host'] AND $type == 'current')
        {
            $_SESSION['prev_host'] = $_SESSION['host'];
            $_SESSION['prev_cred'] = $_SESSION['cred'];
            $_SESSION['prev_priv'] = $_SESSION['priv'];
            unset($_SESSION['host'], $_SESSION['cred'], $_SESSION['priv']);

            return true;
        }

        return false;
    }

    final protected function ErrorLog($message)
    {
        error_log(date('g:i:s A') . "\n" . $message . "\n", 3, PANEL_ERROR_LOG);
    }

    final private function GetCKey()
    {
        return $this->ckey;
    }
}

?>