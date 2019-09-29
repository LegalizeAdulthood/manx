<?php

require_once 'test/FakeCurlApi.php';
require_once 'pages/UrlInfo.php';

class TestUrlInfo extends PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $curlApi = new FakeCurlApi();
        $curl = new UrlInfo(null, $curlApi);
        $this->assertFalse($curlApi->initCalled);
        $this->assertNotNull($curl);
    }

    public function testSizeReturns4096()
    {
        $curlApi = new FakeCurlApi();
        $curlApi->initFakeResult = 0xdeadbeef;
        $curlApi->execFakeResult = "HTTP/1.0 200 OK\n"
            . "Content-Length: 4096\n"
            . "\n";
        $curlApi->getinfoFakeResult = 200;
        $url = 'http://bitsavers.org/WhatsNew.txt';
        $curl = new UrlInfo($url, $curlApi);
        $size = $curl->size();
        $this->assertTrue($curlApi->initCalled);
        $this->assertTrue($curlApi->setoptCalled);
        $this->assertTrue($curlApi->execCalled);
        $this->assertTrue($curlApi->getinfoCalled);
        $this->assertTrue($curlApi->closeCalled);
        $this->assertEquals(4096, $size);
    }

    public function testGetLastModified()
    {
        $curlApi = new FakeCurlApi();
        $curlApi->initFakeResult = 0xdeadbeef;
        $curlApi->execFakeResult = "HTTP/1.0 200 OK\n"
            . "Last-Modified: Wed, 15 Nov 1995 04:58:08 GMT\n"
            . "\n";
        $curlApi->getinfoFakeResult = 200;
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $curl = new UrlInfo($url, $curlApi);
        $lastModified = $curl->lastModified();
        $this->assertTrue($curlApi->initCalled);
        $this->assertTrue($curlApi->setoptCalled);
        $this->assertTrue($curlApi->execCalled);
        $this->assertTrue($curlApi->getinfoCalled);
        $this->assertTrue($curlApi->closeCalled);
        $this->assertEquals(strtotime('Wed, 15 Nov 1995 04:58:08 GMT'), $lastModified);
    }

    public function test404ErrorGivesSizeOfFalse()
    {
        $curlApi = new FakeCurlApi();
        $curlApi->initFakeResult = 0xdeadbeef;
        $curlApi->execFakeResult = "HTTP/1.0 404 Not found\n"
            . "\n";
        $curlApi->getinfoFakeResult = 404;
        $url = 'http://foo.example.com/bogusDocument.pdf';
        $curl = new UrlInfo($url, $curlApi);
        $size = $curl->size();
        $this->assertTrue($size === false);
    }
}
