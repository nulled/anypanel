<?php

// MUST extend PanelCommon
// It can not be abstract or interface
// class can be made final ie 'final class Template
class Template extends PanelCommon
{
    // must be public so Panel::LoadModule() can read it's contents
    public $menu;

    function __construct()
    {
        $menu = array(
        'Program Paths' => 'program_paths',
        'System Config' => 'sysconfig'
        );

        // BuildMenu('ClassName', $menu, 'methods to hide from demo') <--- will set url and html menu label to 'ClassName'
        // BuildMenu('ClassName:ModName', $menu, 'methods to hide from demo') <--- will set url to 'ClassName' and html menu label to 'ModName'
        $this->menu = $this->BuildMenu('Template:Menu Label', $menu, 'sysconfig');
    }

    // home() must exist
    // all methods should be either public or private
    // private methods are for use in this class only
    // public methods are made public so that they can be instantiated from another module
    final public function home()
    {
        require $this->Page(__METHOD__);
        // or prefered above method for uniformity
        require_once PANEL_PHTML . '/Template/home.php';
    }

    final public function program_paths()
    {
        require $this->Page(__METHOD__);
    }

    final private function sysconfig()
    {
        require $this->Page(__METHOD__);
    }

    final private function private_utility()
    {

    }
}

?>