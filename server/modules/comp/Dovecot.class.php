<?php

class Dovecot extends PanelCommon
{
    public $menu;

    function __construct()
    {
        $menu = array(
        'Settings' => 'settings',
        'Accounts' => 'accounts'
        );

        $this->menu = $this->BuildMenu('Dovecot', $menu);
    }

    final public function home()
    {
        require $this->Page(__METHOD__);
    }
}

?>
