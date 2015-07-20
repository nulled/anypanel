<?php

class Network extends PanelCommon
{
    public $menu = array();

    function __construct()
    {
        $menu = array(
        'Network Map' => 'network_map' // :Network/network_map.js:Network/network_map.css'
        );

        $this->menu = $this->BuildMenu('Network', $menu);
    }

    final public function home()
    {
        require_once $this->Page(__METHOD__);
    }

    // Process to Sockets
    // open Sockets to Process
    // live tcpdump
    // all clickable to next process/file

    final public function network_map()
    {
        $data  = $this->RemoteExec('netcat -nl');
        //$data .= $this->RemoteExec('lsof');

        require_once $this->Page(__METHOD__);
    }
}

?>