<?php

require_once 'pages/IUrlInfoFactory.php';

class FakeUrlInfoFactory implements IUrlInfoFactory
{
    function createUrlInfo($url)
    {
        $this->createUrlInfoCalled = true;
        $this->createUrlInfoLastUrl = $url;
        return $this->createUrlInfoFakeResult;
    }
    public $createUrlInfoCalled, $createUrlInfoLastUrl, $createUrlInfoFakeResult;
}
