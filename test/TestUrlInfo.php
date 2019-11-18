<?php

require_once 'pages/UrlInfo.php';

class TestUrlInfo extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->_curl = $this->createMock(ICurlApi::class);
        $this->_url = 'http://bitsavers.org/pdf/IndexByDate.txt';
        $this->_info = new UrlInfo($this->_url, $this->_curl);
        $this->_session = 0xdeadbeef;
        $this->_curl->expects($this->once())->method('init')->willReturn($this->_session);
    }

    public function testSizeReturnsContentLength()
    {
        $this->_curl->expects($this->once())->method('exec')->with($this->_session)->willReturn("HTTP/1.0 200 OK\n"
            . "Content-Length: 4096\n"
            . "\n");
        $this->_curl->expects($this->once())->method('getinfo')->with($this->_session, CURLINFO_HTTP_CODE)->willReturn(200);

        $size = $this->_info->size();

        $this->assertEquals(4096, $size);
    }

    public function testGetLastModified()
    {
        $this->_curl->method('exec')->with($this->_session)->willReturn("HTTP/1.0 200 OK\n"
            . "Last-Modified: Wed, 15 Nov 1995 04:58:08 GMT\n"
            . "\n");
        $this->_curl->method('getinfo')->with($this->_session, CURLINFO_HTTP_CODE)->willReturn(200);

        $lastModified = $this->_info->lastModified();

        $this->assertEquals(strtotime('Wed, 15 Nov 1995 04:58:08 GMT'), $lastModified);
    }

    public function test404ErrorGivesSizeOfFalse()
    {
        $this->_curl->expects($this->once())->method('exec')->with($this->_session)->willReturn("HTTP/1.0 404 Not found\n"
            . "\n");
        $this->_curl->expects($this->once())->method('getinfo')->with($this->_session, CURLINFO_HTTP_CODE)->willReturn(404);

        $size = $this->_info->size();

        $this->assertTrue($size === false);
    }

    public function testExistsHttpStatus200()
    {
        $this->_curl->expects($this->once())->method('exec')->with($this->_session)->willReturn("HTTP/1.0 200 OK\n"
            . "\n");
        $this->_curl->expects($this->once())->method('getinfo')->with($this->_session, CURLINFO_HTTP_CODE)->willReturn(200);

        $result = $this->_info->exists();

        $this->assertTrue($result);
    }

    public function testExistsHttpStatus404()
    {
        $this->_curl->expects($this->once())->method('exec')->with($this->_session)->willReturn("HTTP/1.0 404 Not found\n"
            . "\n");
        $this->_curl->expects($this->once())->method('getinfo')->with($this->_session, CURLINFO_HTTP_CODE)->willReturn(404);

        $result = $this->_info->exists();

        $this->assertFalse($result);
    }

    public function testExistsHttpStatus301()
    {
        $this->_curl->expects($this->exactly(2))->method('exec')
            ->with($this->_session)
            ->willReturn("HTTP/1.0 301 Permanently moved\n"
                . "Location: http://bitsavers.org/pdf/IndexByDate.txt\n"
                . "\n",
                "HTTP/1.0 200 OK\n"
                . "Last-Modified: Wed, 15 Nov 1995 04:58:08 GMT\n"
                . "\n");
        $this->_curl->expects($this->exactly(2))->method('getinfo')
            ->with($this->_session, CURLINFO_HTTP_CODE)
            ->willReturn(301, 200);

        $result = $this->_info->exists();

        $this->assertTrue($result);
    }

    public function testExistsHttpStatus302()
    {
        $this->_curl->expects($this->exactly(2))->method('exec')
            ->with($this->_session)
            ->willReturn("HTTP/1.0 302 Temporarily moved\n"
                . "Location: http://bitsavers.org/pdf/IndexByDate.txt\n"
                . "\n",
                "HTTP/1.0 200 OK\n"
                . "Last-Modified: Wed, 15 Nov 1995 04:58:08 GMT\n"
                . "\n");
        $this->_curl->expects($this->exactly(2))->method('getinfo')
            ->with($this->_session, CURLINFO_HTTP_CODE)
            ->willReturn(302, 200);

        $result = $this->_info->exists();

        $this->assertTrue($result);
    }

    public function testExistsHttpStatus0()
    {
        $this->_curl->expects($this->once())->method('exec')->with($this->_session)->willReturn("");
        $this->_curl->expects($this->once())->method('getinfo')->with($this->_session, CURLINFO_HTTP_CODE)->willReturn(0);

        $result = $this->_info->exists();

        $this->assertFalse($result);
    }

    private $_session;
    /** @var string */
    private $_url;
    /** @var ICurlApi */
    private $_curl;
    /** @var UrlInfo */
    private $_info;
}
