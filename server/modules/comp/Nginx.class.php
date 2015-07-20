<?php

class Nginx extends PanelCommon
{
    public $menu = array();

    private $config           = array();
    private $nginx_parameters = array();
    private $contexts         = array();
    private $line_number      = null;
    private $line_msg         = null;
    private $notValid         = '';
    private $nginx_conf       = '';
    private $html             = '';
    private $js_selects       = '';

    private $nginx_conf_filename = '/etc/nginx/nginx.conf';
    private $sites_avail         = '/etc/nginx/sites-available';
    private $sites_enabled       = '/etc/nginx/sites-enabled';

    private $filename_param_raw;
    private $filename_param_urls;
    private $nginx_param_url = 'http://nginx.org/en/docs/dirindex.html';

    function __construct()
    {
        $menu = array(
        'Configuration' => 'nginx_conf:Nginx/nginx_conf.js:Nginx/nginx_conf.css',
        'Manage Sites'  => 'virtual_sites:Nginx/virtual_sites.js:Nginx/virtual_sites.css'
        );

        $this->menu = $this->BuildMenu('Nginx', $menu);

		$this->filename_param_urls = PANEL_TEMPLATE_PATH . '/nginx_urls.txt';
		$this->filename_param_raw  = PANEL_TEMPLATE_PATH . '/nginx_raw.txt';

        $this->nginx_parameters = $this->parse_nginx_raw_to_array();

        $this->contexts = array('main', 'events', 'http', 'server', 'location', 'any');
    }

    final public function home()
    {
        require $this->Page(__METHOD__);
    }

    final public function virtual_sites()
    {
        $submitted = $this->Request('submitted');
        $file      = $this->Request('file');

        $_file = $this->sites_avail . '/' . $file;

        $this->save_nginx_conf();

        $this->enable_disable_site($submitted, $file);

        if ($this->Submitted($submitted) == 'load' AND ! strstr($file, ';'))
        {
            $which = $this->IsFileThere($this->sites_enabled . '/' . $file, '-h');

            $buttons = (! $which) ? '<span id="buttons"><button name="' . $file . '" id="disabled">Enable Site</button></span> | ' :
                                    '<button name="'.$file.'" id="enabled">Disable Site</button></span> | ';

            if ($this->IsFileThere($this->sites_avail . '/' . $file))
            {
                $this->nginx_conf_filename = $this->sites_avail . '/' . $file;

                if ($this->config_file_to_array())
                {
                    $this->config_array_to_html();
                    $this->js_selects();
                }
            }

            foreach (xdebug_get_declared_vars() as $val)
                $log .= "$val = ${$val}\n";
            $this->ErrorLog($log);
        }

        $site_filename = $this->nginx_conf_filename;

        $site_list = $this->build_site_list();

        require_once $this->Page(__METHOD__);
    }

    final public function build_site_list()
    {
        $available = $this->RemoteExec('ls -A ' . $this->sites_avail . '/.');
        $enabled   = $this->RemoteExec('ls -A ' . $this->sites_enabled .  '/.');

        $avail_files = $enabled_files = $sites = array();

        foreach (array('avail_files' => $available, 'enabled_files' => $enabled) as $key => $files)
        {
            $list = array();

            foreach (explode("\n", $files) as $line)
            {
                $line = $this->PackWhiteSpace(trim($line));
                $line = explode(' ', $line);

                foreach ($line as $file)
                {
                    $file = trim($file);
                    if (substr($file, strlen($file)-4) != '.bak' AND substr($file, strlen($file)-4) != '.wrk')
                        $list[] = $file;
                }
            }

            if ($key == 'avail_files')   $avail_files   = $list;
            if ($key == 'enabled_files') $enabled_files = $list;
        }

        $sites        = '<div class="site_list">' . "\n";
        $site_enabled = '<span class="site_enabled">';
        $site_avail   = '<span class="site_avail">';

        $i = 0;
        foreach ($avail_files as $file)
        {
            if (! ($i % 5) AND $i) $sites .= "<br />\n";
            $sites .= (in_array($file, $enabled_files)) ? $site_enabled . $file . "</span>\n" : $site_avail . $file . "</span>\n";
            $i++;
        }

        $sites .= "</div>\n";

        return $sites;
    }

    final public function nginx_conf()
    {
        $this->save_nginx_conf();

        $this->config_file_to_array();

        $this->config_array_to_html();

        $this->js_selects();

        require_once $this->Page(__METHOD__);
    }

    final private function save_nginx_conf()
    {
        $file = $this->nginx_conf_filename;

        if ($this->Submitted($this->Request('submitted')) == 'save')
        {
            $_SESSION['nginx_conf_wrk'] = 0;

            if ($nginx_conf = $this->Request('config_result'))
            {
                // send to remove server
                $rand = '/tmp/anypanel_' . mt_rand();
                $this->SFTP('put', array($rand, $nginx_conf));
                $this->RemoteExec('mv ' . $rand .  ' ' . $file .  '.wrk');

                $this->RemoteExec('cp ' . $file .  '.wrk ' . $file);

                $rtn = $this->RemoteExec('nginx -t');

                $conf_test_passed = (stristr($rtn, 'syntax is ok') OR stristr($rtn, 'test is successful')) ? 1 : 0;

                if ($conf_test_passed)
                {
                    $this->RemoteExec('cp ' . $file . ' ' . $file . '.bak');
                    $this->notValid = 'Configuration Successfully Saved.';
                }
                else
                {
                    list($tmp) = explode("\n", $rtn);
                    list(, $msg, $line_number) = explode(':', trim($tmp));
                    list($msg) = explode(' in /', trim($msg));

                    $msg         = trim($msg);
                    $line_number = trim($line_number);

                    $this->line_msg = $msg;

                    $this->line_number = (is_numeric($line_number)) ? $line_number : null;

                    $this->RemoteExec('cp ' . $file . '.bak ' . $file);
                    $this->notValid = "ERROR: nginx -t:  {$msg}:{$line_number}";
                }

                $nginx_conf = $this->RemoteExec('cat ' . $file . '.wrk');

                if (! is_string($nginx_conf) OR ! strlen($nginx_conf) > 1)
                    exit('FATAL: unable to cat ' . $file . '.wrk');
                else
                    $this->nginx_conf = $nginx_conf;
            }
        }
    }

    final private function config_file_to_array()
    {
        $i         = 0;
        $context   = 'main';
        $file      = $this->nginx_conf_filename;

        // 0 = nginx.conf
        // 1 = sites-available
        $mode = (dirname($file) == '/etc/nginx') ? 0 : 1;

        if ($this->IsFileThere($file))
        {
            // work with .wrk file on remote
            if ($_SESSION['nginx_conf_wrk'])
            {
                if (! $this->IsFileThere($file . '.wrk'))
                    $this->RemoteExec('cp ' . $file . ' ' . $file . '.wrk');
            }
            else
            {
                $_SESSION['nginx_conf_wrk'] = 1;
                $this->RemoteExec('cp ' . $file . ' ' . $file . '.wrk');
            }

            $this->RemoteExec('cp ' . $file . ' ' . $file . '.bak');
            $nginx_conf_txt = $this->RemoteExec('cat ' . $file . '.wrk');
        }
        else // use default nginx.conf if /etc/nginx/nginx.conf is missing
        {
            //exit('FATAL ERROR: default not implamented');
            // use default local file
            $_SESSION['nginx_conf_wrk'] = 1;
            $default_filename = PANEL_TEMPLATE_PATH . '/nginx/default/nginx.conf';
            $nginx_conf_txt = file_get_contents($default_filename);
            $nginx_conf_txt = str_replace('[username]', 'www-data', $nginx_conf_txt);
            //$nginx_conf_txt = "\nevents {\n\n}\n\nhttp {\n\n}\n";
        }

        //$this->ErrorLog($nginx_conf_txt);

        if (is_string($nginx_conf_txt))
        {
            foreach (explode("\n", $nginx_conf_txt) as $line)
            {
                $line = trim($line);

                list($param, $val) = explode(' ', $this->PackWhiteSpace($line), 2);

                if (in_array($param, $this->contexts))
                    $context = $param;
                else if (strstr($line, '}'))
                {
                    if ( ($comment = strpos($line, '#')) !== false AND $comment < strpos($line, '}')) {}
                    else
                    {
                        $this->config[$i][$context] = array('curly_end' => $line);
                        $context = ($mode == 1 AND $context == 'location') ? 'server' : 'main';
                        $i++;
                        continue;
                    }
                }

                if (in_array($context, $this->contexts))
                {
                    if (@in_array($context, $this->nginx_parameters[$param]['Context']) OR @in_array('any', $this->nginx_parameters[$param]['Context']))
                        $this->config[$i][$context] = array($param => $val);
                    else
                        $this->config[$i][$context] = $line;
                }
                else
                    exit("FATAL: context:'$context' not allowed in config_file_to_array()");

                $i++;
            }

            //$this->ErrorLog(print_r($this->config, 1));
        }
        else
        	exit('FATAL: nginx.conf in config_file_to_array() was not a string: ' . $nginx_conf_txt);
    }

    final private function config_array_to_html()
    {
        $nginx_parameters = $this->nginx_parameters;
        $line_number      = $this->line_number;
        $line_msg         = $this->line_msg;

        // 0 = nginx.conf
        // 1 = sites-available
        $mode = (dirname($this->nginx_conf_filename) == '/etc/nginx') ? 0 : 1;

        //$this->ErrorLog(gettype($this->config));

        if (count($this->config))
        {
            $prev_context = $cart_change = '';

            foreach ($this->config as $i => $arr)
            {
                $p = $v = $curly_end = '';

                $context = key($arr);
                $val = $arr[$context];

                if ($prev_context != $context AND $context != 'main')
                    $cart_change = $context;

                if (is_array($val))
                {
                    $p = key($val);
                    $v = $val[$p];

                    if ($p == 'curly_end')
                    {
                        $p = '';
                        $curly_end = 1;
                    }
                }
                else
                    $v = $val;

                if (preg_match('/["]/', $v) != false)
                    $v = htmlentities($v);

                if ($cart_change)
                {
                    if ($prev_context == 'location' AND $cart_change != 'location')
                        $output .= "</div>\n".'<span class="curly_end">'.$v."</span><br />\n";
                    else if ($cart_change == 'location')
                    {
                        list($m, $d) = explode(' ', $v);

                        $this->html .= '<div id="cart_'.$cart_change.'">' . "\n";
                        $this->html .= '<span class="location">'.$p.'</span>' . "\n";
                        $this->html .= '<span class="param">' . $this->make_select($context, $i, $m, 'location') . '</span>' . "\n";
                        $this->html .= '<span class="param"><input type="text" class="nginx_conf" value="'.$d.'" /></span>' . "\n";
                        $this->html .= '<span class="curly_location">{</span>' . "\n";
                        $this->html .= '<img onclick="this.getParent().getNext().destroy();this.getParent().getNext().destroy();this.getParent().destroy();" src="client/img/negative_sign.png">' . "\n";
                        $this->html .= '<br />' . "\n";
                    }
                    else
                        $this->html .= '<span class="curly_begin">'.$v.'<img onclick="var p=this.getParent(); p.getNext().destroy(); p.getNext().destroy(); p.getNext().destroy(); p.getNext().destroy(); if (p.getNext()) p.getNext().destroy(); if (p.getNext()) p.getNext().destroy(); if (! $(\'cart_main\').getElements(\'span\')[1]) p.getParent().adopt(Element(\'span\', {\'class\': \'comment\', \'text\': \'Empty\'})); p.destroy()" src="client/img/negative_sign.png"></span><br /><div id="cart_'.$cart_change.'">'."\n";

                    $cart_change = '';
                }
                else if ($p AND $v)
                {
                    $error_css = (($i+1) == $line_number) ? ' error' : '';
                    $error_msg = (($i+1) == $line_number) ? $line_msg : '';

                    $this->html .= '<span class="param'.$error_css.'">' . $this->make_select($context, $i, $p) . '</span>' . "\n".
                         '<span class="param'.$error_css.'"><input class="nginx_conf" type="text" name="'.$i.'_'.$context.'_v" value="'.$v.'" />'.
                         ' <img src="client/img/negative_sign.png" onclick="var p=this.getParent();p.getPrevious().dispose();p.getNext().dispose();p.dispose();" /> '.$error_msg.'</span><br />'."\n";
                }
                else if (! $p AND ! $v)
                    $this->html .= '<span class="empty"></span><br />'."\n";
                else if ($curly_end)
                    $this->html .= "</div>\n".'<span class="curly_end">'.$v."</span><br />\n";
                else
                    $this->html .= '<span class="comment">'.$v."</span><br />\n";

                $prev_context = $context;
            }
        }
    }

    final private function make_select($context, $i, $p, $type_select = 'normal')
    {
        $nginx_parameters = $this->nginx_parameters;

        if ($type_select == 'normal')
        {
            $options = '<select name="'.$i.'_'.$context.'_p" class="nginx_conf" title="" onchange="this.getParent().getNext().getChildren()[0].value=this.options[this.options.selectedIndex].label">'."\n";

            foreach ($nginx_parameters as $_p => $arr)
            {
                if (! in_array($context, $nginx_parameters[$_p]['Context']) AND ! @in_array('any', $nginx_parameters[$_p]['Context']))
                    continue;

                $x = addslashes(trim($nginx_parameters[$_p]['Syntax']));
                $s = trim($nginx_parameters[$_p]['Summary']);
                $d = addslashes(trim($nginx_parameters[$_p]['Default']));

                if (preg_match('/["]/', $s) != false)
                    $s = htmlentities($s);

                //$x = $s = $d = '';

                $isSelected = ($p == $_p) ? ' selected="selected"' : '';
                $options .= '<option data-syntax="'.$x.'" label="'.$d.'" title="'.$s.'" value="'.$_p.'"'.$isSelected.'>'.$_p.'</option>'."\n";
            }

            $options .= '</select>';
        }
        else if ($type_select == 'location')
        {
            $options = '<select name="'.$i.'_'.$context.'_p" class="nginx_conf" title="No Modifier" onchange="this.title=this.options[this.options.selectedIndex].title">'."\n";

            foreach (array('-' => 'No Modifier', '=' => 'Exact Match', '~' => '(regex) Case Sensitive', '~*' => '(regex) Case Insensitive', '^~' => '(regex) are Ignored') as $k => $v)
            {
                $isSelected = ($k == $p)  ? ' selected="selected"' : '';
                $_p         = ($k == '-') ? '' : $k;

                $options .= '<option data-syntax="" label="" title="'.$v.'" value="'.$p.'"'.$isSelected.'>'.$_p.'</option>'."\n";
            }

            $options .= '</select>';
        }
        else
            exit('FATAL ERROR: Nginx->make_select() illegal $type_select');

        return $options;
    }

    final private function js_selects()
    {
        $nginx_parameters = $this->nginx_parameters;
        $contexts         = $this->contexts;

        $jsScript  = "<script>\nvar opt = Element('option', {'data-syntax': '', 'value': '', 'text': '-- Select One --'});\n";
        $onchange  = "this.getParent().getNext().getChildren()[0].value=this.options[this.options.selectedIndex].label;";

        foreach ($nginx_parameters as $pn => $parr)
        {
            $x = addslashes(trim($parr['Syntax']));
            $d = trim($parr['Default']);
            $s = addslashes(trim($parr['Summary']));

            //$d = $s = $x = '';

            foreach ($contexts as $context)
            {
                if (in_array($context, $parr['Context']))
                {
                    if (! strstr($jsScript, $context . '_select = new Element('))
                        $jsScript .= "{$context}_select = new Element('select', {'name': 'main', 'class': 'nginx_conf', 'onchange': '{$onchange}'});\n{$context}_select.adopt(opt.clone());\n";

                    $jsScript .= "{$context}_select.adopt(Element('option', {'value': '{$pn}', 'text': '{$pn}', 'data-syntax': '{$x}', 'label': '{$d}', 'title': '{$s}'}));\n";
                }
            }
        }

        $jsScript .= "selects = [";

        foreach ($contexts as $context)
            $jsScript .= "'{$context}_select',{$context}_select,";

        $jsScript = substr($jsScript, 0, strlen($jsScript)-1);
        $jsScript .= "];
        </script>
        ";

        $this->js_selects = $jsScript;
    }

    final private function enable_disable_site($submitted, $file)
    {
        if ($this->Submitted($submitted) == 'disable')
        {
            $this->RemoteExec('rm -f /etc/nginx/sites-enabled/' . $file);
            exit;
        }

        if ($this->Submitted($submitted) == 'enable')
        {
            $this->RemoteExec('ln -s /etc/nginx/sites-available/' . $file . ' /etc/nginx/sites-enabled/' . $file);
            exit;
        }
    }

    final private function download_nginx_directives()
    {
        $this->download_nginx_param_urls();
        $this->download_nginx_param();
        return $this->parse_nginx_raw_to_array();
    }

    final private function download_nginx_param_urls()
    {
        $html = file_get_contents($this->nginx_param_url);

        list(, $html) = explode('<a href="http://nginx.com/blog/">blog</a>', $html, 2);

        $html = str_replace('<br>', "\n", trim($html));
        $html = strip_tags($html, '<a>');
        $html = str_replace('href="', 'href="http://nginx.org/en/docs/', trim($html));

        file_put_contents($this->filename_param_urls, $html);
    }

    final private function download_nginx_param()
    {
        $data = file_get_contents($this->filename_param_urls);

        unlink($this->filename_param_raw);

        foreach (explode("\n", $data) as $line)
        {
            // <a href="http://nginx.org/en/docs/ngx_http_auth_basic_module.html#auth_basic_user_file">auth_basic_user_file</a>
            // <a href="http://nginx.org/en/docs/mail/ngx_mail_auth_http_module.html#auth_http">auth_http</a>
            list(, $t) = explode('<a href="', $line);
            list($url, $t) = explode('">', $t);
            list($param) = explode('</a>', $t);

            //echo "$param $url\n";

            $page = file_get_contents($url);

            list(, $page) = explode('<a name="' . $param . '"', $page, 2);

            $page = preg_replace('/\h+/', ' ', strip_tags(trim($page)));
            $page = str_replace("\n", ' ', $page);
            $page = preg_replace('/\h+/', ' ', trim($page));
            $page = substr($page, 2);

            file_put_contents($this->filename_param_raw, trim($page) . ' ', FILE_APPEND);
        }
    }

    final private function parse_nginx_raw_to_array()
    {
        $params = array();
        $data   = file_get_contents($this->filename_param_raw);
        $offset = 0;

        while ($offset !== false)
        {
            $pos1   = strpos($data, 'Syntax: ', $offset);
            $syntax = substr($data, $pos1, 6);

            $pos2    = strpos($data, 'Default: ', $offset);
            $default = substr($data, $pos2, 7);

            $pos3    = strpos($data, 'Context: ', $offset);
            $context = substr($data, $pos3, 7);

            $syntax_value  = substr($data, $pos1 + 8, $pos2 - ($pos1 + 8));
            $default_value = substr($data, $pos2 + 9, $pos3 - ($pos2 + 9));

            list($parameter_name) = explode(' ', $syntax_value);

            $work_str = substr($data, ($pos3 + 9), 100);

            $context_value = array();

            foreach (explode(' ', $work_str) as $c)
            {
                // in context 'if in location' is a context messing up the array
                // if anyone else wants to fix in future, have at it
                if (strstr($c, 'if'))
                    break;

                if (strstr($c, ','))
                    $context_value[] = substr($c, 0, strlen($c)-1);
                else
                {
                    $context_value[] = $c;
                    break;
                }
            }

            $size_str = 0;
            $size_arr = count($context_value) - 1;
            $str_len = $size_arr * 2 + 1;

            foreach ($context_value as $c)
                $size_str += strlen($c);

            $str_len_events = $size_str + $str_len;

            $pos4 = $pos3 + 9 + $str_len_events;

            $offset = strpos($data, 'Syntax: ', $pos4);

            // internal; no value used, skipping for now
            if ($parameter_name != 'internal;' AND
                $parameter_name != 'empty_gif;' AND
                $parameter_name != 'f4f;' AND
                $parameter_name != 'flv;' AND
                $parameter_name != 'least_conn;' AND
                $parameter_name != 'hls;' AND
                $parameter_name != 'upstream_conf;' AND
                $parameter_name != 'mp4;' AND
                $parameter_name != 'status;' AND
                $parameter_name != 'stub_status;' AND
                $parameter_name != 'ip_hash;' AND
                // context holders, skip em
                $parameter_name != 'events'  AND
                $parameter_name != 'http'   AND
                $parameter_name != 'mail'   AND
                $parameter_name != 'server' AND
                $parameter_name != 'location')
            {
                $summary_value = substr($data, $pos4, $offset - $pos4);

                list(, $default_value) = explode(' ', $default_value, 2);

                $parameters[$parameter_name][$syntax]   = $syntax_value;
                $parameters[$parameter_name][$default]  = $default_value;
                $parameters[$parameter_name][$context]  = $context_value;
                $parameters[$parameter_name]['Summary'] = $this->RemoveCtrlChars($summary_value);
            }
        }

        return $parameters;
    }
}

?>