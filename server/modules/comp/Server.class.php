<?php

class Server extends PanelCommon
{
    public $menu;

    function __construct()
    {
        $menu = array(
        'Accounts' => 'accounts:Server/accounts.js',
        'Software' => 'software',
        'Hardware' => 'hardware:Server/hardware.js:Server/hardware.css',
        'Terminal' => 'terminal'
        );

        $this->menu = $this->BuildMenu('Server', $menu, 'terminal');

        //exit('<pre>'.print_r($this->menu, 1).'</pre>');
    }

    final public function home()
    {
       require $this->Page(__METHOD__);
    }

    final public function terminal() // made private to disable in demo
    {
        $screen = $this->RemoteExec("screen -li");
        //$out = $this->RemoteExec("screen -li | grep panel | sed -e 's/\s/ /g' | cut -d' ' -f2,6");
        //list($panel, $state) = explode(' ', trim($this->PackWhiteSpace($out)));

        if (stristr($screen, 'currently not installed'))
            $screen = "<hr /><pre>Screen is not installed on this Server:</pre><button onclick=\"poll('install','screen')\">Install Screen</button>";
        else
            $screen = "<pre>$screen</pre>";

        require $this->Page(__METHOD__);
    }

    final public function accounts()
    {
        $title_add_edit_server = 'Add a New Server to Manage';
        $table_list_style      = 'width:850px';
        $table_add_style       = 'width:500px';

        $servers   = array();

        $modules   = $this->Request('modules');
        $submitted = $this->Request('submitted');
        $authtype  = $this->Request('authtype');
        $host      = $this->Request('host');

        if ($this->Submitted($submitted) == 'delete')
            list($notValid, $submitted) = $this->delete($host);
        else if ($this->Submitted($submitted) == 'load_edit')
            list($notValid, $authtype, $title_add_edit_server, $newhost, $newaccount, $newpass1, $newpass2, $newpass3, $newpass4, $modules) = $this->load_edit($host);
        else if ($this->Submitted($submitted) == 'select')
            list($notValid, $submitted) = $this->select($host);
        else if (($this->Submitted($submitted) == 'add' OR $this->Submitted($submitted) == 'edit') AND $authtype == 'interactive')
        {
            $table_add_style = 'width:700px;';
            $title_add_edit_server = ucfirst($submitted) . ' Server - Interactive Username and Password';
            list($notValid, $submitted, $authtype, $newhost, $newaccount) = $this->add_edit_interactive($authtype);
        }
        else if (($this->Submitted($submitted) == 'add' OR $this->Submitted($submitted) == 'edit') AND $authtype == 'privhostkey')
        {
            $table_add_style = 'width:800px;';
            $title_add_edit_server = ucfirst($submitted) . ' Server - RSA Private Host Key';
            list($notValid, $submitted, $authtype, $newhost, $newaccount, $privkey) = $this->add_edit_private_key($authtype);
        }

        if ($_SESSION['username'] === 'demo')
            $notValid = 'NOTICE: demo can not perform this action';

        if (strstr($authtype, '_'))
            list($authtype, $submitted) = explode('_', $authtype);

        if ($this->Query("SELECT ipaddress, modules, distro, prompts, active FROM servers WHERE ownername='{$_SESSION['username']}' ORDER BY ipaddress"))
            while (list($i, $m, $d, $p, $a) = $this->FetchRow())
                $servers[$i] = array('modules' => $m, 'distro' => $d, 'prompts' => $p, 'active' => $a);

        $modulesHTML = $this->MooToolsMultiSelect('modules', $this->GetAvailableModules(), $modules);

        require $this->Page(__METHOD__);
    }

    final public function software()
    {
        $program = $this->Request('program', 'rawurldecode');
        $path    = $this->Request('path');
        $action  = $this->Request('action');

        if ($program AND $action === 'manpage')
            $manpage = $this->manpage($program);
        if ($action === 'update')
        {
            $apt_get = $this->apt_get($action, $program);
            //exit($apt_get);
        }
        else if ($program AND $action === 'dpkg')
            list($info, $dpkg) = $this->dpkg($program);
        else if ($path == 'listinstalledpackages')
        {
            list($epath, $env_paths, $path, $num) = $this->get_env_paths($path, 0);
            $rand = $this->RandomString();
            $list_pkgs = $this->RemoteExec("dpkg -l > /tmp/$rand; cat /tmp/$rand; rm -f /tmp/$rand");
        }
        else
        {
            // get version of a binary
            // dpkg -l $(dpkg -S $pp|cut -d':' -f1)|grep '^ii'|tr -s ' '|cut -d' ' -f3
            $file = $version = '';
            $programs = $files = $versions = array();

            list($epath, $env_paths, $path, $num) = $this->get_env_paths($path);

            if ($path AND $num)
            {
                $out = $this->RemoteExec("ls $path");
                //exit('<pre>'.$out.'</pre>');

                foreach (explode("\n", $out) as $p)
                {
                    if (! ($p = trim($p))) continue;
                    $file .= "file $path/$p;";
                    //$version .= "dpkg -l \$(dpkg -S {$path}/{$p}|cut -d':' -f1)|grep '^ii'|tr -s ' '|cut -d' ' -f3;";
                }
                //exit($file);

                $out = $this->RemoteExec(trim($file));
                //exit('<pre>'.$out.'</pre>');

                foreach (explode("\n", $out) as $p)
                {
                    if (! ($p = trim($p)) OR strpos($p, 'symbolic') !== false) continue;
                    list($pp, $type) = explode(':', $p, 2);
                    $_pp = basename($pp);
                    $files[$_pp] = $type;
                    $programs[$pp] = $_pp;
                    // WAY too slow ... gets the binary package version
                    //$versions[$_pp] = $this->RemoteExec("dpkg -l \$(dpkg -S {$pp}|cut -d':' -f1)|grep '^ii'|tr -s ' '|cut -d' ' -f3");
                }

                unset($out, $file, $pp, $_pp, $type, $p);
            }
        }

        require $this->Page(__METHOD__);
    }

    final public function hardware()
    {
        // http://us1.php.net/manual/en/reference.pcre.pattern.syntax.php
        // http://donsnotes.com/tech/charsets/ascii.html
        //$top  = $this->RemoteExec('top', 1);
        //$top  = $this->MakeSingleSpace(preg_replace('/(\x1b(.*?)\x0f|\x1b(.*?)m|\x1b(.*?)\[k|\x1b(.*?)1h)/i', '', $top));
        // 
        // SI  \x0F shift in
        // ESC \x1B escape
        $load = $this->RemoteExec('uptime');
        $ram  = $this->RemoteExec('free');
        $cpu  = $this->RemoteExec('cat /proc/cpuinfo');
        $df   = $this->RemoteExec('df -h');
        $if   = $this->RemoteExec('ifconfig');

        require $this->Page(__METHOD__);
    }

    final private function delete($host)
    {
        $this->Query("DELETE FROM servers WHERE ipaddress='$host' AND ownername='{$_SESSION['username']}' LIMIT 1");

        if ($_SESSION['host'] == $this->Request('host'))
        {
            $_SESSION['host']    = $_SESSION['cred']    = $_SESSION['priv']    = '';
            $_SESSION['pubhost'] = $_SESSION['uprompt'] = $_SESSION['rprompt'] = '';
            $_SESSION['FIRST_EXEC'] = '';
            $_SESSION['mods'] = 'Server';
        }

        $notValid = 'Successfully Deleted the Server: ' . $host;
        $submitted = '';
    }

    final private function select($host)
    {
        if ($this->Query("SELECT ipaddress, credentials, modules, privhostkey, prompts, distro
                          FROM servers WHERE ipaddress='$host' AND ownername='{$_SESSION['username']}' AND active='1' LIMIT 1"))
        {
            list($newhost, $cred, $mods, $priv, $prompts, $distro) = $this->FetchRow();

            $_SESSION['host'] = $newhost;
            $_SESSION['cred'] = $cred;
            $_SESSION['priv'] = $priv;
            $_SESSION['mods'] = $mods;
            $_SESSION['pubhost'] = $_SESSION['uprompt'] = $_SESSION['rprompt'] = '';
            $_SESSION['distro_name'] = $_SESSION['distro_version'] = '';

            if ($distro AND strstr($distro, ' '))
                list($_SESSION['distro_name'], $_SESSION['distro_version']) = explode(' ', $distro);

            if ($prompts AND strstr($prompts, ' '))
                list($_SESSION['uprompt'], $_SESSION['rprompt']) = explode(' ', $prompts);

            if (! $_SESSION['uprompt'] OR ! $_SESSION['distro_name'])
            {
                $distro = $this->GetDistroType(1);

                if (is_array($distro))
                {
                    $_SESSION['distro_name']    = $distro[0];
                    $_SESSION['distro_version'] = $distro[1];
                }

                if ($_SESSION['distro_name'] AND $_SESSION['distro_version'])
                {
                    $distro = $this->EscapeString($_SESSION['distro_name'] . ' ' . $_SESSION['distro_version']);
                    $this->Query("UPDATE servers SET distro='$distro' WHERE ipaddress='$host' AND ownername='{$_SESSION['username']}' LIMIT 1");
                }

                if ($_SESSION['uprompt'] AND $_SESSION['rprompt'])
                {
                    $prompts = $this->EscapeString($_SESSION['uprompt'] . ' ' . $_SESSION['rprompt']);
                    $this->Query("UPDATE servers SET prompts='$prompts' WHERE ipaddress='$host' AND ownername='{$_SESSION['username']}' LIMIT 1");
                }
            }

            $notValid = 'Successfully Selected the Server: ' . $_SESSION['host'];
            $submitted = '';
        }

        return array($notValid, $submitted);
    }

    final private function load_edit($host)
    {
        if ($this->Query("SELECT ipaddress, credentials, modules, privhostkey
                          FROM servers WHERE ipaddress='$host' AND ownername='{$_SESSION['username']}' AND active='1'"))
        {

            list($newhost, $cred, $mods, $priv) = $this->FetchRow();

            //exit("$newhost, $cred, $mods, $priv");

            $modules = explode(' ', $mods);

            list($newaccount, $newpass1, $newpass3) = explode(' ', $this->Decrypt($cred));

            if ($newpass1) $newpass2 = $newpass1;
            if ($newpass3) $newpass4 = $newpass3;
            if ($priv) $privkey = $this->Decrypt($priv);

            $authtype = ($priv) ? 'privhostkey' : 'interactive';
            $title2   = ($priv) ? 'RSA Private Host Key' : 'Interactive Username and Password';

            $authtype .= '_edit';

            $title_add_edit_server = 'Edit Server - ' . $title2;

            $notValid = 'Successfully Loaded for Editing.';
        }

        return array($notValid, $authtype, $title_add_edit_server, $newhost, $newaccount, $newpass1, $newpass2, $newpass3, $newpass4, $modules);
    }

    final private function add_edit_interactive($authtype)
    {
        $newhost    = $this->Request('newhost');
        $newaccount = $this->Request('newaccount');
        $newpass1   = $this->Request('newpass1');
        $newpass2   = $this->Request('newpass2');
        $modules    = $this->Request('modules');
        $submitted  = $this->Request('submitted');

        if (! $newhost OR ! $newaccount OR ! $newpass1 OR ! $newpass2)
            $notValid = 'ERROR: Missing <i>Required</i> Parameters.';
        else if (! $this->IsIPAddress($newhost))
            $notValid = 'ERROR: <b>Host</b> must be a valid IPv4 Address.';
        else if (! $this->IsLinuxAccount($newaccount))
            $notValid = 'ERROR: <b>Linux Account Name</b> may contain a-z and Dashes (-), but must begin with a Letter. Length between 2-8 Characters.';
        else if (! $this->IsLinuxPassword($newpass1))
            $notValid = 'ERROR: <b>Password</b> must not contain Spaces, ~ or Backticks (`). Length between 6-16 Characters.';
        else if ($newpass1 !== $newpass2)
            $notValid = 'ERROR: <b>Passwords</b> do not match.';
        else if ($submitted == 'add' AND $this->Query("SELECT ipaddress FROM servers WHERE ipaddress='{$newhost}' LIMIT 1"))
            $notValid = 'ERROR: <b>Host/IP Address</b> already Taken.';
        else if ($submitted == 'edit' AND ! $this->Query("SELECT ipaddress FROM servers WHERE ipaddress='{$newhost}' LIMIT 1"))
            $notValid = 'ERROR: <b>Host/IP Address</b> not found, No changes made.';
        else
        {
            $modules = (is_array($modules) AND count($modules)) ? trim('Server ' . implode(' ', $modules)) : 'Server';

            $_SESSION['mods'] = $modules;

            $this->SessionSwap('current');

            $_SESSION['host'] = $newhost;
            $_SESSION['cred'] = $creds = $this->Encrypt($newaccount . ' ' . $newpass1);
            $_SESSION['priv'] = '';

            $distro = ($distro = $this->GetDistroType()) ? $distro[0] . ' ' . $distro[1] : 'unknown';

            if (! $this->SessionSwap('previous'))
            {
                $_SESSION['host'] = '';
                $_SESSION['cred'] = '';
                $_SESSION['priv'] = '';
            }

            $this->Query("INSERT INTO servers (ipaddress, ownername, credentials, modules, distro, active, modified, created)
                VALUES('$newhost','{$_SESSION['username']}','$creds','$modules','$distro', '1', NOW(), NOW())
                ON DUPLICATE KEY UPDATE credentials='$creds', modules='$modules', distro='$distro', active='1', modified=NOW()");

            //$this->Query("REPLACE INTO servers
            //                   (ipaddress, ownername,   credentials,   modules,   distro,  privhostkey, active, $date_fields)
            //             VALUES('$newhost','{$_SESSION['username']}','$creds','$modules','$distro', '',          '1',    $date_data)");

            $notValid = 'Successfully ' . ucfirst($submitted) . 'ed Server ' . $newhost;
            $authtype = $submitted = '';
        }

        return array($notValid, $submitted, $authtype, $newhost, $newaccount);
    }

    final private function add_edit_private_key($authtype)
    {
        $newhost    = $this->Request('newhost');
        $newaccount = $this->Request('newaccount');
        $privkey    = $this->Request('privkey');
        $newpass1   = $this->Request('newpass1');
        $newpass2   = $this->Request('newpass2');
        $newpass3   = $this->Request('newpass3');
        $newpass4   = $this->Request('newpass4');
        $submitted  = $this->Request('submitted');
        $modules    = $this->Request('modules');

        if (! $newhost OR ! $newaccount OR ! $privkey)
            $notValid = 'ERROR: Missing <i>Required</i> Parameters.';
        else if (! $this->IsIPAddress($newhost))
            $notValid = 'ERROR: <b>Host</b> must be a valid IPv4 Address.';
        else if (! $this->IsLinuxAccount($newaccount))
            $notValid = 'ERROR: <b>Linux Account Name</b> may contain a-z and Dashes (-), but must begin with a Letter. Length between 2-8 Characters.';
        else if ($this->GetPrivateHostKeyType($privkey) != 'RSA')
            $notValid = 'ERROR: Private Host Key must be RSA.';
        else if (! $this->IsLinuxPassword($newpass1))
            $notValid = 'ERROR: Linux Account <b>Password</b> must not contain Spaces, ~ or Backticks (`). Length between 6-16 Characters.';
        else if ($newpass1 != $newpass2)
            $notValid = 'ERROR: Linux Account <b>Passwords</b> do not match.';
        else if ($newpass3 AND ! $this->IsLinuxPassword($newpass3))
            $notValid = 'ERROR: RSA <b>Password</b> must not contain Spaces, ~ or Backticks (`). Length between 6-16 Characters.';
        else if ($newpass3 AND ! ($newpass3 == $newpass4))
            $notValid = 'ERROR: RSA <b>Passwords</b> do not match.';
        else if ($submitted == 'add' AND $this->Query("SELECT ipaddress FROM servers WHERE ipaddress='{$newhost}' LIMIT 1"))
            $notValid = 'ERROR: <b>Host/IP Address</b> already Taken.';
        else if ($submitted == 'edit' AND ! $this->Query("SELECT ipaddress FROM servers WHERE ipaddress='{$newhost}' LIMIT 1"))
            $notValid = 'ERROR: <b>Host/IP Address</b> not found, so unable to make any changes.';
        else
        {
            $this->SessionSwap('current');

            $_SESSION['host'] = $newhost;
            $_SESSION['cred'] = $credentials = $this->Encrypt(trim($newaccount . ' ' . $newpass1 . ' ' . $newpass3));
            $_SESSION['priv'] = $privkey = $this->Encrypt($privkey);

            $distro = ($distro = $this->GetDistroType()) ? $distro[0] . ' ' . $distro[1] : 'unknown';

            if (! $this->SessionSwap('previous'))
            {
                $_SESSION['host'] = '';
                $_SESSION['cred'] = '';
                $_SESSION['priv'] = '';
            }

            $modules = (is_array($modules) AND count($modules)) ? trim('Server ' . implode(' ', $modules)) : 'Server';

            if ($submitted == 'add')
            {
                $date_fields = 'modified, created';
                $date_data   = 'NOW(),    NOW()';
            }
            else // edit
            {
                $date_fields = 'modified';
                $date_data   = 'NOW()';
            }

            $this->Query("REPLACE INTO servers
                                (ipaddress, ownername,          credentials,   modules,   distro,  privhostkey, active, $date_fields)
                         VALUES('$newhost','{$_SESSION['username']}','$credentials','$modules','$distro','$privkey','1',$date_data))");

            $notValid = 'Successfully ' . ucfirst($submitted) . 'ed Server ' . $newhost;
            $authtype = $submitted = '';
        }

        return array($notValid, $submitted, $authtype, $newhost, $newaccount, $privkey);
    }

    final private function manpage($program)
    {
        $manpage = ($this->IsShellSafe($program)) ? $this->RemoteExec("man -P 'cat -s' $program " . $this->filter_colors) : 'unsafe';

        if (stristr($manpage, 'man 7 undocumented'))
            $manpage = $this->RemoteExec('man 7 undocumented');

        return $manpage;
    }

    final private function dpkg($program)
    {
        $info = $dpkg = '';

        $dpkg = ($this->IsShellSafe($program)) ? $this->RemoteExec("dpkg --search $program") : 'path found matching pattern';

        if (! strstr('path found matching pattern', $dpkg))
        {
            list($dpkg) = explode(':', $dpkg);
            $title = "Package: $dpkg";

            if ($dpkg) $files  = $this->RemoteExec("dpkg --listfiles $dpkg");
            if ($dpkg) $status = $this->RemoteExec("dpkg --status $dpkg");

            $status = wordwrap(trim($status), 150, '<br />');

            $info = $status . "<hr /><b>Files this Package has Installed:</b><hr />$files";
        }

        return array($info, $dpkg);
    }

    final private function apt_get($action, $program)
    {
        $allowed_actions = array('install','update','upgrade','purge','remove','dist-upgrade','autoremove','clean');

        $data = $this->RemoteExec('apt-get ' . $action);

        return trim($data);

    }

    final private function get_env_paths($path, $get_count = 1)
    {
        $env_paths = array();

        $epath = $this->RemoteExec('echo $PATH');

        foreach (explode(':', trim($epath)) as $p)
        {
            if ($get_count)
            {
                $c = $this->RemoteExec("ls $p | wc -l");
                if (! trim($c)) $c = 0;
                if ($path == $p)
                {
                    $pathOK = $p;
                    $num = $c;
                }
            }
            $env_paths[$p] = $p;
        }

        if ($get_count)
            $path = ($pathOK) ? $pathOK : '';

        return array($epath, $env_paths, $path, $num);
    }
}

?>