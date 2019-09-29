<?php

require_once 'test/FakeCurlApi.php';
require_once 'test/FakeFile.php';
require_once 'pages/UrlTransfer.php';
require_once 'pages/Config.php';

class TestUrlTransfer extends PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $curlApi = new FakeCurlApi();
        $fileApi = new FakeFileFactoryApi();
        $url = 'http://bitsavers.org/Whatsnew.txt';

        $transfer = new UrlTransfer($url, $curlApi);

        $this->assertNotNull($transfer);
    }

    public function testGet()
    {
        $curlApi = new FakeCurlApi();
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $transfer = new UrlTransfer($url, $curlApi);

        $transfer->get($destination);

        $this->assertTrue($curlApi->initCalled);
        $this->assertTrue($curlApi->setoptCalled);
        $this->assertTrue($curlApi->execCalled);
        $this->assertTrue($curlApi->getinfoCalled);
    }
}
