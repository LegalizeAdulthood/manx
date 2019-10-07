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
        $file = $this->createMock(IFile::class);
        $stream = 10;
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $tempDestination = $destination . '.tmp';
        $this->_fileSystem->expects($this->once())->method('openFile')->with($tempDestination)->willReturn($file);
        $file->expects($this->once())->method('getStream')->willReturn($stream);
        $session = 66;
        $this->_curlApi->expects($this->once())->method('init')->willReturn($session);
        $this->_curlApi->expects($this->once())->method('setopt')->with($session, CURLOPT_FILE, $stream);
        $this->_curlApi->expects($this->once())->method('exec')->with($session)->willReturn(false);
        $this->_curlApi->expects($this->once())->method('getinfo')->with($session)->willReturn(404);
        $this->_curlApi->expects($this->once())->method('close')->with($session);
        $transfer = $this->createInstance($url);

        $result = $transfer->get($destination);

        $this->assertFalse($result);
    }

    public function testGetSuccessNoOverwrite()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $file = $this->createMock(IFile::class);
        $stream = 10;
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $tempDestination = $destination . '.tmp';
        $this->_fileSystem->expects($this->once())->method('openFile')->with($tempDestination, 'w')->willReturn($file);
        $file->expects($this->once())->method('getStream')->willReturn($stream);
        $session = 66;
        $this->_curlApi->expects($this->once())->method('init')->willReturn($session);
        $this->_curlApi->expects($this->once())->method('setopt')->with($session, CURLOPT_FILE, $stream);
        $this->_curlApi->expects($this->once())->method('exec')->with($session)->willReturn(false);
        $this->_curlApi->expects($this->once())->method('getinfo')->with($session)->willReturn(200);
        $this->_curlApi->expects($this->once())->method('close')->with($session);
        $file->expects($this->once())->method('close');
        $this->_fileSystem->expects($this->once())->method('fileExists')->with($destination)->willReturn(false);
        $this->_fileSystem->expects($this->never())->method('unlink');
        $this->_fileSystem->expects($this->once())->method('rename')->with($tempDestination, $destination);
        $transfer = $this->createInstance($url);

        $result = $transfer->get($destination);

        $this->assertTrue($result);
    }

    public function testGetSuccessWithOverwrite()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $file = $this->createMock(IFile::class);
        $stream = 10;
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $tempDestination = $destination . '.tmp';
        $this->_fileSystem->expects($this->once())->method('openFile')->with($tempDestination, 'w')->willReturn($file);
        $file->expects($this->once())->method('getStream')->willReturn($stream);
        $session = 66;
        $this->_curlApi->expects($this->once())->method('init')->willReturn($session);
        $this->_curlApi->expects($this->once())->method('setopt')->with($session, CURLOPT_FILE, $stream);
        $this->_curlApi->expects($this->once())->method('exec')->with($session)->willReturn(false);
        $this->_curlApi->expects($this->once())->method('getinfo')->with($session)->willReturn(200);
        $this->_curlApi->expects($this->once())->method('close')->with($session);
        $file->expects($this->once())->method('close');
        $this->_fileSystem->expects($this->once())->method('fileExists')->with($destination)->willReturn(true);
        $this->_fileSystem->expects($this->once())->method('unlink')->with($destination);
        $this->_fileSystem->expects($this->once())->method('rename')->with($tempDestination, $destination);
        $transfer = $this->createInstance($url);

        $result = $transfer->get($destination);

        $this->assertTrue($result);
    }

    private $_curlApi;
    private $_fileSystem;
}
