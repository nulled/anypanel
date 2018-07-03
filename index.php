<?php

//xdebug_enable();

define('PANEL_CKEY', 'zS7hgPk5fBhNZG64F87h6hfD'); // 24 chars length
define('PANEL_CACHE_VERSION', '1000');
define('PANEL_AUTH_SALT', 'KdKdj87FdnWkdjjCH86Sjdh'); // any length
define('PANEL_VERSION', '0.0.2');
define('PANEL_DOMAIN', 'localhost'); // (localhost) or (.planetxmail.com) include dot at end for session cookie param
define('PANEL_BASE_PATH', '/home/nulled/anypanel');
define('PANEL_DOMAIN_PATH', '/anypanel');
define('PANEL_TEMPLATE_PATH', PANEL_BASE_PATH . '/server/templates');
define('PANEL_ERROR_LOG', '/var/log/panel_error_log');

set_include_path(get_include_path() . PATH_SEPARATOR . PANEL_BASE_PATH . '/server/modules/core/phpseclib0.3.9');

require_once PANEL_BASE_PATH . '/server/modules/core/MySQLi_Access.core.class.php';
require_once PANEL_BASE_PATH . '/server/modules/core/PanelCommon.core.class.php';
require_once PANEL_BASE_PATH . '/server/modules/core/Panel.core.class.php';
require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Net/SFTP.php'; // class SFTP extends SSH2
require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Crypt/RSA.php';
require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Crypt/AES.php';
require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/File/ANSI.php';

function callback_error($errno, $errstr, $errfile, $errline, $errcon)
{
    switch ($errno)
    {
        case E_ERROR:               $errno = 'E_ERROR';                break;
        case E_WARNING:             $errno = 'E_WARNING';              break;
        case E_PARSE:               $errno = 'E_PARSE';                break;
        case E_NOTICE:              $errno = 'E_NOTICE';               break;
        case E_CORE_ERROR:          $errno = 'E_CORE_ERROR';           break;
        case E_CORE_WARNING:        $errno = 'E_CORE_WARNING';         break;
        case E_COMPILE_ERROR:       $errno = 'E_COMPILE_ERROR';        break;
        case E_COMPILE_WARNING:     $errno = 'E_COMPILE_WARNING';      break;
        case E_USER_ERROR:          $errno = 'E_USER_ERROR';           break;
        case E_USER_WARNING:        $errno = 'E_USER_WARNING';         break;
        case E_USER_NOTICE:         $errno = 'E_USER_NOTICE';          break;
        case E_STRICT:              $errno = 'E_STRICT';               break;
        case E_RECOVERABLE_ERROR:   $errno = 'E_RECOVERABLE_ERROR';    break;
        case E_DEPRECATED:          $errno = 'E_DEPRECATED';           break;
        case E_USER_DEPRECATED:     $errno = 'E_USER_DEPRECATED';      break;
        default: break;
    }

    echo "<b>$errno</b>: <i>$errstr</i><br />\n";
    echo "<b>$errfile $errline</b><br />\n";
    die;
}

//set_error_handler('callback_error', E_ALL & ~E_NOTICE & ~E_WARNING);
set_error_handler('callback_error', E_ALL & ~E_NOTICE);

$anypanel = new AnyPanel();

?>
