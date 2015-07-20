<?php

class Postfix extends PanelCommon
{
    public $menu;

    function __construct()
    {
        $menu = array(
        'Settings'  => 'settings',
        'Mailboxes' => 'mailboxes'
        );

        $this->menu = $this->BuildMenu('Postfix', $menu);
    }

    final public function home()
    {
        require $this->Page(__METHOD__);
    }
}

?>
