<?php

require_once 'pages/UrlTransfer.php';

class FakeUrlTransfer implements IUrlTransfer
{
    public function __construct()
    {
        $this->getCalled = false;
    }

    public function get($destination)
    {
        $this->getCalled = true;
        $this->getLastDestination = $destination;
    }
    public $getCalled, $getLastDestination;
}
