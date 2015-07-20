<?php

class Bind extends PanelCommon
{
    public $menu;

    function __construct()
    {
        $menu = array(
        'Hardware' => 'hardware:Server/hardware.js:Server/hardware.css'
        );

        $this->menu = $this->BuildMenu('Bind:DNS', $menu, '');
    }

    final public function home()
    {
    }

/*
/etc/bind/
-rw-r--r--   1 root bind bind.keys
-rw-r--r--   1 root bind db.0
-rw-r--r--   1 root bind db.127
-rw-r--r--   1 root bind db.255
-rw-r--r--   1 root bind db.empty
-rw-r--r--   1 root bind db.freeadplanet.com
-rw-r--r--   1 root bind db.freesecretsoftware.com
-rw-r--r--   1 root bind db.local
-rw-r--r--   1 root bind db.planetxmail.com
-rw-r--r--   1 root bind db.root
-rw-r--r--   1 root bind db.targetedadplanet.com
-rw-r--r--   1 root bind named.conf
-rw-r--r--   1 root bind named.conf.default-zones
-rw-r--r--   1 root bind named.conf.local
-rw-r--r--   1 root bind named.conf.options
-rw-r-----   1 bind bind rndc.key
-rw-r--r--   1 root bind zones.rfc1918

$TTL    604800
@       IN      SOA     ns1.planetxmail.com. root.planetxmail.com. (
                             20         ; Serial
                         604800         ; Refresh
                          86400         ; Retry
                        2419200         ; Expire
                         604800 )       ; Negative Cache TTL
;
@                               IN      NS      ns1.planetxmail.com.
@                               IN      A       205.252.250.4
bar.planetxmail.com.            IN      CNAME   foo.planetxmail.com.
ns1                             IN      A       205.252.250.4
www                             IN      A       205.252.250.4
planetxmail.com.                IN      MX      10      planetxmail.com.
_dmarc.planetxmail.com.         IN      TXT     "v=DMARC1; p=none; rua=mailto:tap3@planetxmail.com"
planetxmail.com.                IN      TXT     "v=spf1 ip4:205.252.250.4 a mx ~all"
_domainkey.planetxmail.com.     IN      TXT     "o=-\; r=tap3@planetxmail.com"
mail._domainkey                 IN      TXT     "v=DKIM1\; g=*\; k=rsa\; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDTmwfedTYwuBIQVm/ihn808pOBwg/Ib8v59if7kP0vcFPZZ12+sKYyje0tgpr6vG3ZnwNmIV6VktvoZbPNzSIUa0MVNeFMYoKIiU16CM3mKLIigRC0dV/4whDVWVFPHntLie0YUdUieM0qEgrW+W6iitwDXQHAco4dhtEoMWlHkwIDAQAB"
*/

    final public function add_record_soa($domainName)
    final public function remove_record_soa($domainName)

    final public function add_record_ns($domainName)
    final public function remove_record_ns($domainName)

    final public function add_record_mx($domainName)
    final public function remove_record_mx($domainName)

    final public function add_record_a($domainName)
    final public function remove_record_a($domainName)

    final public function add_record_cname($domainName)
    final public function remove_record_cname($domainName)

    final public function add_record_txt($domainName)
    final public function remove_record_txt($domainName)
}

?>