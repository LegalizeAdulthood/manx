<?php

require_once 'pages/BitSaversPage.php';

class FakeWhatsNewPageFactory implements IWhatsNewPageFactory
{
    function __construct()
    {
        $this->createUrlInfoCalled = false;
        $this->createUrlTransferCalled = false;
        $this->getCurrentTimeCalled = false;
    }

    function createUrlInfo($url)
    {
        $this->createUrlInfoCalled = true;
        $this->createUrlInfoLastUrl = $url;
        return $this->createUrlInfoFakeResult;
    }
    public $createUrlInfoCalled,
        $createUrlInfoLastUrl, $createUrlInfoFakeResult;

    function createUrlTransfer($url)
    {
        $this->createUrlTransferCalled = true;
        $this->createUrlTransferLastUrl = $url;
        return $this->createUrlTransferFakeResult;
    }
    public $createUrlTransferCalled,
        $createUrlTransferLastUrl, $createUrlTransferFakeResult;

    function getCurrentTime()
    {
        $this->getCurrentTimeCalled = true;
        return $this->getCurrentTimeFakeResult;
    }
    public $getCurrentTimeCalled, $getCurrentTimeFakeResult;
}
