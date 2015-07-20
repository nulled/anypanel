<?php

// http://www.linux.org/threads/undelete-files-on-linux-systems.4316/
// NOTE: Delete ALL files using 'srm' (secure remove) apt-get install secure-delete
//
// Place this script permanently in /etc/nginx/, it contains no sensitive data.
// Create a file 'ckpass' containing the cipher key that decodes the 'ckencoded' file.
// This creates a file 'ck', which nginx will include as a fastcgi PHP $_SERVER variable.
// Restart/Reload nginx
// Delete 'ckpass', 'ck' to remove any trace of the raw cipher key.
// Now, the cipher key only exists in nginx memory!
// Regenerate 'ck' when needed, using this script tool.

// For the ultra paranoid, and the highest level of security, but also the most inconvienant,
// delete 'ck, 'ckpass' AND 'ckencoded'.  You will then need the script to regenerate 'ckencoded'.
// This is usually only nessasary when a threat is detected, or your server is at risk of getting
// physically stolen.  NEVER EVER have the tool that generates 'ckencoded' on the server any longer
// than it takes to run it to do it's job.

define('PANEL_BASE_PATH', '/home/nulled/www');
set_include_path(get_include_path() . PATH_SEPARATOR . PANEL_BASE_PATH . '/server/modules/core/phpseclib');

require_once PANEL_BASE_PATH . '/server/modules/core/phpseclib/Crypt/AES.php';

$cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
$cipher->setKey(file_get_contents('./ckpass'));
$text = $cipher->decrypt(base64_decode(file_get_contents('./ckencoded')));
unlink('./ck');
file_put_contents('./ck', $text);

?>