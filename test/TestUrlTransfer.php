<?php

require_once 'pages/UrlTransfer.php';
require_once 'pages/Config.php';

class TestUrlTransfer extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->_curlApi = $this->createMock(ICurlApi::class);
        $this->_fileSystem = $this->createMock(IFileSystem::class);
    }

    private function createInstance($url)
    {
        return new UrlTransfer($url, $this->_curlApi, $this->_fileSystem);
    }

    public function testConstruct()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';

        $transfer = $this->createInstance($url);

        $this->assertNotNull($transfer);
    }

    public function testGetFailure()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $this->_curlApi->expects($this->once())->method('init');
        $this->_curlApi->expects($this->once())->method('exec');
        $this->_curlApi->expects($this->once())->method('getinfo')->willReturn(404);
        $this->_curlApi->expects($this->once())->method('close');
        $this->_fileSystem->expects($this->never())->method('openFile');
        $transfer = $this->createInstance($url);
        $destination = PRIVATE_DIR . 'Whatsnew.txt';

        $result = $transfer->get($destination);

        $this->assertFalse($result);
    }

    public function testGetSuccessNoOverwrite()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $this->_curlApi->method('getinfo')->willReturn(200);
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $tempDestination = $destination . '.tmp';
        $stream = $this->createMock(IFile::class);
        $this->_fileSystem->expects($this->once())->method('openFile')->with($this->equalTo($tempDestination), $this->equalTo('w'))->willReturn($stream);
        $contents = "This is the contents";
        $this->_curlApi->expects($this->once())->method('exec')->willReturn($contents);
        $transfer = $this->createInstance($url);
        $stream->expects($this->once())->method('write')->with($this->equalTo($contents));
        $stream->expects($this->once())->method('close');
        $this->_fileSystem->expects($this->once())->method('fileExists')->with($this->equalTo($destination))->willReturn(false);
        $this->_fileSystem->expects($this->never())->method('unlink');
        $this->_fileSystem->expects($this->once())->method('rename')->with($this->equalTo($tempDestination), $this->equalTo($destination));

        $result = $transfer->get($destination);

        $this->assertTrue($result);
    }

    public function testGetSuccessWithOverwrite()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $this->_curlApi->method('getinfo')->willReturn(200);
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $tempDestination = $destination . '.tmp';
        $stream = $this->createMock(IFile::class);
        $this->_fileSystem->method('openFile')->willReturn($stream);
        $contents = "This is the contents";
        $this->_curlApi->expects($this->once())->method('exec')->willReturn($contents);
        $transfer = $this->createInstance($url);
        $this->_fileSystem->expects($this->once())->method('fileExists')->with($this->equalTo($destination))->willReturn(true);
        $this->_fileSystem->expects($this->once())->method('unlink')->with($this->equalTo($destination));
        $this->_fileSystem->expects($this->once())->method('rename')->with($this->equalTo($tempDestination), $this->equalTo($destination));

        $result = $transfer->get($destination);

        $this->assertTrue($result);
    }

    private $_curlApi;
    private $_fileSystem;
}
