<?php

final class Panel extends PanelCommon
{
    private   $ckey = null;
    protected $role, $core, $module, $method, $modules;
    private   $apt_get_install = "DEBIAN_FRONTEND='noninteractive' apt-get -o Dpkg::Options::='--force-confnew' -y install ";

    public function __construct()
    {
        $this->ckey = (string) $_SERVER['CKEY'];
        $this->ckey = 'zS7hgPk5fBhNZG64F87h6hfD';
        unset($_SERVER['CKEY'], $GLOBALS['CKEY']);

        if (! is_string($this->ckey) OR strlen($this->ckey) != 24)
            exit('ERROR: panel key not 24 characters in length');

        parent::__construct($this->ckey);

        $this->asset_random = 'a' . substr(sha1(mt_rand()), 0, 8);

        $this->role   = $this->Request('role', 'lcase');
        $this->core   = $this->Request('core', 'lcase');
        $this->module = $this->Request('module');
        $this->method = $this->Request('method');

        $this->Main();
    }

    final private function Main()
    {
        $session_state = $this->SessionStart();

        if ($session_state === true)
        {
            if ($has_output = $this->RunCore())
                exit($has_output);

            if (! $this->method)
                $this->method = 'home';

            if (! $this->module)
                require_once $this->Page('main');
            else
                $this->RunModule();
        }
        else
            $this->Login($session_state);
    }

    final private function CreateMenu()
    {
        $menu = array();

        $modules = explode(' ', $this->modules);

        foreach ($modules as $module)
        {
            $this->LoadModule($module);
            $current_class = new $module;
            $menuKey = key($current_class->menu);
            list($moduleName, $moduleLabel) = explode(':', $menuKey, 2);
            if (! $moduleLabel) $moduleLabel = $moduleName;
            $menu[$moduleName . ':' . $moduleLabel] = $current_class->menu[$menuKey];
            unset($current_class);
        }

        return $menu;
    }

    final private function RunModule()
    {
        //exit('1:'.$this->module);
        $this->LoadModule($this->module);
        //exit('2:'.$this->ckey);

        $comp = new $this->module;
        $comp->RunMethod($this->module, $this->method, $this->ckey);
    }

    final private function LoadModule($classname)
    {
        if (! $this->modules)
        {
            $this->SessionDestroy();
            $this->Login('ERROR: No modules defined.');
            exit;
        }

        $classes = explode(' ', $this->modules);

        if (! in_array($classname, $classes))
            exit('ERROR: LoadModule found unknown Class/Module');

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
    }

    final private function Login($notValid = '')
    {
        $username  = $this->Request('username', 'addslashes');
        $password  = $this->Request('password');
        $submitted = $this->Request('submitted', 'lcase');

        $this->SessionCookieParams();
        session_start();
        $_SESSION = array();
        session_unset();
        session_destroy();

        $this->SessionCookieParams();
        session_start();

        if ($submitted === 'login')
        {
            $sha1pass = sha1($password);

            if (! $username OR ! $password)
                $notValid = 'ERROR: <i>Missing</i> Username and/or Password.';
            else if (! in_array($this->role, $this->roles_allowed))
                $notValid = 'ERROR: <i>Missing</i> Panel Role.';
            else if (! $this->Query("SELECT ownername FROM users WHERE ownername='{$username}' AND password='{$sha1pass}' LIMIT 1"))
                $notValid = 'ERROR: <i>Invalid</i> Username and/or Password.';
            else if (! $this->Query("SELECT ownername FROM users WHERE ownername='{$username}' AND password='{$sha1pass}' AND active='1' LIMIT 1"))
                $notValid = 'ERROR: This Account has been <b><i>Deactivated</i></b>.';
            else
            {
                $_SESSION['LAST_ACTIVITY'] = time();
                $_SESSION['time_start']    = time();
                $_SESSION['username']      = $username;
                $_SESSION['auth']          = sha1($_SESSION['username'] . PANEL_AUTH_SALT . $_SESSION['time_start'] . $this->role);
                $_SESSION['mods']          = 'Server';
                $_SESSION['role']          = $this->role;

                if ($username === 'demo')
                {
                    if ($this->Query("SELECT ipaddress, credentials, modules, privhostkey, prompts, distro FROM servers WHERE ownername='demo' LIMIT 1"))
                    {
                        list($newhost, $cred, $mods, $priv, $prompts, $distro) = $this->FetchRow();

                        $_SESSION['host']    = $newhost;
                        $_SESSION['cred']    = $cred;
                        $_SESSION['priv']    = $priv;
                        $_SESSION['mods']    = $mods;
                        $_SESSION['pubhost'] = '';

                        list($_SESSION['uprompt'], $_SESSION['rprompt']) = explode(' ', $prompts);
                        list($_SESSION['distro_name'], $_SESSION['distro_version']) = explode(' ', $distro);
                    }
                }

                $this->Query("UPDATE users SET modified=NOW() WHERE ownername='{$username}' AND password='{$sha1pass}' LIMIT 1");

                header('Location: index.php');
                exit;
            }
        }

        $_SESSION['role'] = $this->role;

        $opts = array('-- Choose a Role --' => '');

        foreach ($this->roles_allowed as $role)
        {
            if ($role == 'admin')
                $opts['Administrator'] = $role;
            else
                $opts[ucfirst($role)] = $role;
        }

        $select_roles = $this->MakeSingleSelect('role', $opts, $_SESSION['role']);

        require_once $this->Page('login');
    }

    final private function RunCore()
    {
        if ($this->core === 'pollaction')
            return $this->CorePollAction();

        if ($this->core === 'poll')
            return $this->CorePoll();

        if ($this->core === 'headhtml')
            return $this->CoreHeadHTML();

        if ($this->core === 'menuhtml')
            return $this->CoreMenuHTML();

        // we want ajax to fill main based on role
        // for now it is determined in phtml/main.php
        //if ($this->core === 'mainhtml')
            //return $this->CoreMainHTML();

        return false;
    }

    final private function CoreMenuHTML()
    {
        $menu = $this->CreateMenu();

        $html = '';

        foreach ($menu as $myComp => $action)
        {
            list($modName, $modLabel) = explode(':', $myComp);
            $html .= '<h3 id="toggler" class="toggler"><a class="menu_title" href="javascript:ajaxGET(\'index.php?module='.$modName.'\')">'.$modLabel.'</a></h3>
            <div id="element" class="element">' . "\n";

            if (is_array($action))
            {
                foreach ($action as $label => $page)
                {
                    list($method, $jsURL, $cssURL) = explode(':', $page);//            ajaxGET(getUrl, showPopup, jsUrl, cssUrl, divContain)
                    $html .= "<a class=\"menu_url\" href=\"javascript:ajaxGET('index.php?module={$modName}&method={$method}',0,'{$jsURL}','{$cssURL}')\">{$label}</a><br />\n";
                }
            }

            $html .= '</div>' . "\n";
        }

        return $html;
    }

    final private function CoreHeadHTML()
    {
        $screen      = ($_SESSION['pscreen']) ? $_SESSION['pscreen'] : 'No Terminal Open';
        $curauthtype = ($_SESSION['priv']) ? 'RSA Private Host Key' : 'Keyboard-Interactive';
        $server_info = "{$_SESSION['host']} " . ucfirst(strtolower($_SESSION['distro_name'])) . " {$_SESSION['distro_version']} $curauthtype $screen<br />
                        {$_SESSION['uprompt']}<br />
                        {$_SESSION['rprompt']}";
        $server_info =  ($_SESSION['host']) ? $server_info : 'No Server Connected';

        if ($_SESSION['role'] == 'admin')    $img_title = 'title_anypanel.png';
        if ($_SESSION['role'] == 'reseller') $img_title = 'title_resellerpanel.png';
        if ($_SESSION['role'] == 'client')   $img_title = 'title_clientpanel.png';

        return '
        <div class="head_img">
            <a href="index.php"><img src="client/img/' . $img_title . '" border="0" width="178" height="48" /></a>
        </div>
        <div class="head_server">
        ' . $server_info . '
        </div>
        <div class="head_login">
            Logged in as: ' . $_SESSION['username'] . ' - <a class="menu_url" href="index.php?core=logout">Log Out</a>
        </div>
        <div class="clear_float"></div>
        ';
    }

    final private function CorePollAction()
    {
        $actions = array();
        $actions['install'] = array('screen');
        $actions['apt-get'] = array('update','upgrade','autoremove');

        $action = $this->Request('action');
        $item   = $this->Request('item');
        $rand   = $this->Request('rand');

        if (! ctype_alnum($rand))
            return 'rand is not alpha-numeric';

        if ($action == 'nohup' OR $action == 'pscreen')
        {
            $method = $action;

            if ($action === 'nohup')
                unset($_SESSION['pscreen']);
            else
                $_SESSION['pscreen'] = $_SESSION['pscreen2'];
        }
        else
        {
            if (! isset($actions[$action]))
                return 'action not allowed';

            if (! in_array($item, $actions[$action]))
                return 'item not allowed';

            $method = 'nohup';

            unset($_SESSION['pscreen']);

            // we do not want the polling to start until poll
            // initialization is finished, so do not close session
            //session_write_close();
        }

        if ($this->IsDistroSupported(array($_SESSION['distro_name'], $_SESSION['distro_version'])))
        {
            switch ($action)
            {
                case 'install':
                    $cmd = 'apt-get -y install ' . $item;
                    break;

                case 'apt-get':
                    $cmd = 'apt-get -y ' . $item;
                    break;

                // very insecure, as cmd could be anything, only user:nulled has access for testing
                case 'nohup':
                case 'pscreen':
                    $cmd = $item;
                    break;
                default:
            }

            $rand = $_SESSION['pscreen'] ? $_SESSION['pscreen'] : $rand;

            $pid = $this->RemoteExec($cmd, $rand, $method);

            return "Process C:'$cmd' R:$rand M:$method P:$pid";
        }
        else
            return "ERROR: Linux: {$_SESSION['distro_name']} Version: {$_SESSION['distro_version']} Not Supported.";
    }

    final private function CorePoll()
    {
        $rand = $this->Request('rand');

        if (! ctype_alnum($rand)) return 'Poll_rand_file_not_numeric_Poll';

        $rand = ($_SESSION['pscreen']) ? $_SESSION['pscreen'] : $rand;

        $rand = $this->tmp_prepend . $rand;

        if ($this->IsFileThere("/tmp/{$rand}.erase"))
        {
            $this->RemoteExec("rm -f /tmp/{$rand}*");
            list(, $rand) = explode('_', $rand);
            if ($_SESSION['pscreen'] === $rand OR $_SESSION['pscreen2'] === $rand)
            {
                unset($_SESSION['pscreen'], $_SESSION['pscreen2']);
                return 'Pollpscreen_finished_Pollpscreen';
            }
            return 'Poll_finished_Poll';
        }

        if ($this->IsFileThere("/tmp/{$rand}.pid"))
        {
            $pid = trim($this->RemoteExec("cat /tmp/{$rand}.pid"));
            if (! $pid)
            {
                $this->RemoteExec("rm -f /tmp/{$rand}*");
                return 'Poll_rand_file_pid_invalid_Poll';
            }
            if ($pid AND is_numeric($pid) AND ! $this->IsFileThere("/proc/{$pid}"))
                $this->RemoteExec("touch /tmp/{$rand}.erase");
        }

        $i = 0;
        while (true)
        {
            if ($this->IsFileThere("/tmp/{$rand}"))
                break;
            else
            {
                if ($i > 5) return 'Poll_no_rand_file_Poll';
                sleep(1);
                $i++;
            }
        }

        if ($_SESSION['pscreen'])
        {
            $cmd = "cat -s /tmp/{$rand}";
            $method = 'pscreen';
        }
        else
        {
            $cmd = "cat /tmp/{$rand}";
            $method = 'nohup';
        }

        $out = $this->RemoteExec($cmd, null, $method);

        return ($out) ? $out : 'no output from command';
    }

    final private function SessionDestroy()
    {
        if (! isset($_SESSION))
        {
            $this->SessionCookieParams();
            session_start();
        }

        if (ini_get('session.use_cookies'))
        {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 31536000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        else
            exit('ERROR: session.use_cookies needs to be enabled.');

        $_SESSION = array();
        session_unset();
        session_destroy();
    }

    final private function SessionStart()
    {
        $this->SessionCookieParams();

        session_start();

        //$this->ErrorLog(print_r($_SESSION, 1));

        if ($this->core == 'logout')
        {
            $this->SessionDestroy();
            return 'You have Logged Out.';
        }
        else if (strlen($_SESSION['auth']) == 40)
        {
            $this->modules = (isset($_SESSION['mods'])) ? $_SESSION['mods'] : '';

            if (isset($_SESSION['LAST_ACTIVITY']) AND (time() - $_SESSION['LAST_ACTIVITY'] > ini_get('session.gc_maxlifetime')))
                $_SESSION['auth'] = '';

            $_SESSION['LAST_ACTIVITY'] = time();

            $key = sha1($_SESSION['username'] . PANEL_AUTH_SALT . $_SESSION['time_start'] . $_SESSION['role']);

            if ($_SESSION['auth'] == $key)
                return true;
            else
            {
                $this->SessionDestroy();
                return 'Your Session has Expired.';
            }
        }
        else if (empty($_SESSION))
        {
            $this->SessionDestroy();

            header('Location: index.php');
            exit;
        }
        else
        	return '';
    }

    final private function SessionCookieParams()
    {
        session_set_cookie_params(0, PANEL_DOMAIN_PATH, PANEL_DOMAIN, 0, 1);
    }
};

?>
