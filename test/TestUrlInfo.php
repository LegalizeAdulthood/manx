<?php

require_once 'pages/UrlInfo.php';

class TestUrlInfo extends PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $curlApi = $this->createMock(ICurlApi::class);
        $curlApi->expects($this->never())->method('init');

        $curl = new UrlInfo(null, $curlApi);

        $this->assertNotNull($curl);
    }

    public function testSizeReturnsContentLength()
    {
        $curlApi = $this->createMock(ICurlApi::class);
        $curlApi->expects($this->once())->method('init')->willReturn(0xdeadbeef);
        $curlApi->expects($this->once())->method('exec')->willReturn("HTTP/1.0 200 OK\n"
            . "Content-Length: 4096\n"
            . "\n");
        $curlApi->expects($this->once())->method('getinfo')->willReturn(200);
        $url = 'http://bitsavers.org/WhatsNew.txt';
        $curl = new UrlInfo($url, $curlApi);

        $size = $curl->size();

        $this->assertEquals(4096, $size);
    }

    public function testGetLastModified()
    {
        $curlApi = $this->createMock(ICurlApi::class);
        $curlApi->method('init')->willReturn(0xdeadbeef);
        $curlApi->method('exec')->willReturn("HTTP/1.0 200 OK\n"
            . "Last-Modified: Wed, 15 Nov 1995 04:58:08 GMT\n"
            . "\n");
        $curlApi->method('getinfo')->willReturn(200);
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $curl = new UrlInfo($url, $curlApi);

        $lastModified = $curl->lastModified();

        $this->assertEquals(strtotime('Wed, 15 Nov 1995 04:58:08 GMT'), $lastModified);
    }

    public function test404ErrorGivesSizeOfFalse()
    {
        $curlApi = $this->createMock(ICurlApi::class);
        $curlApi->method('init')->willReturn(0xdeadbeef);
        $curlApi->method('exec')->willReturn("HTTP/1.0 404 Not found\n"
            . "\n");
        $curlApi->method('getinfo')->willReturn(404);
        $url = 'http://foo.example.com/bogusDocument.pdf';
        $curl = new UrlInfo($url, $curlApi);

        $size = $curl->size();

        $this->assertTrue($size === false);
    }
}
