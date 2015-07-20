<?php

// http://www.linux.org/threads/undelete-files-on-linux-systems.4316/
// NOTE: Delete ALL files using 'srm' (secure remove) apt-get install secure-delete
//
// Place this file in /etc/nginx to regenerate 'ckencoded' when needed.  IMMEDIATELY remove
// this script from your server and store it back in compressed and encrypted form elsewhere.
// We recommend ccrypt to encrypt and password protect this file, so you can store it anywhere.

// There are multiple levels of security deployed to ensure the Cipher Key can not be obtained,
// even in the event the hardware is physically stolen.

// Level 1 - 'ck', 'ckencoded' and 'ckgen.php' are delete from server after nginx reload. Cipher Key only
//            exists in memory. All tools and files do not exist on server. THis is the most secure level.
// Level 2 - 'ckencoded' and 'ckgen.php' exist in /etc/nginx so 'ck' can be regenerated. This is secure
//            but not as secure as Level 1.  However, a good balance of convience and security.
// Level 3 - 'ck' is left in /etc/nginx and secure, as long as the server is not Physically stolen.
//            If server is stolen or hacker gets inside as root, will gain access to decrypt Database.
//            This is the most convienant, because nginx restart requires no extract steps.

define('PANEL_BASE_PATH', '/home/nulled/www');
set_include_path(get_include_path() . PATH_SEPARATOR . PANEL_BASE_PATH . '/server/modules/core/phpseclib');

require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Crypt/AES.php';

$cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
$cipher->setKey('jd74jdHS87SQNF7fHFS9639f');
$text = $cipher->encrypt('fastcgi_param CKEY zS7hgPk5fBhNZG64F87h6hfD;');
file_put_contents('./ckencoded', base64_encode($text));

?>