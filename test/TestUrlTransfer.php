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
        $fileFactory = new FakeFileFactory();
        $url = 'http://bitsavers.org/Whatsnew.txt';

        $transfer = new UrlTransfer($url, $curlApi, $fileFactory);

        $this->assertNotNull($transfer);
    }

    public function testGet()
    {
        $curlApi = new FakeCurlApi();
        $fileFactory = new FakeFileFactory();
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $transfer = new UrlTransfer($url, $curlApi, $fileFactory);

        $transfer->get($destination);

        $this->assertTrue($curlApi->initCalled);
        $this->assertTrue($curlApi->setoptCalled);
        $this->assertTrue($curlApi->execCalled);
        $this->assertTrue($curlApi->getinfoCalled);
    }
}
