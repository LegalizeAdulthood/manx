<?php

require_once 'test/FakeCurlApi.php';
require_once 'test/FakeFile.php';
require_once 'pages/UrlTransfer.php';
require_once 'pages/Config.php';

class TestUrlTransfer extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->_curlApi = new FakeCurlApi();
        $this->_fileSystem = new FakeFileSystem();
    }

    private function createInstance($url)
    {
        return new UrlTransfer($url, $this->_curlApi, $this->_fileSystem);
    }

    public function testConstruct()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $curlApi = $this->createMock(ICurlApi::class);
        $fileSystem = $this->createMock(IFileSystem::class);

        $transfer = new UrlTransfer($url, $curlApi, $fileSystem);

        $this->assertNotNull($transfer);
    }

    public function testGetFailure()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $curlApi = $this->createMock(ICurlApi::class);
        $curlApi->expects($this->once())->method('init');
        $curlApi->expects($this->once())->method('exec');
        $curlApi->expects($this->once())->method('getinfo')->willReturn(404);
        $curlApi->expects($this->once())->method('close');
        $fileSystem = $this->createMock(IFileSystem::class);
        $fileSystem->expects($this->never())->method('openFile');
        $transfer = new UrlTransfer($url, $curlApi, $fileSystem);
        $destination = PRIVATE_DIR . 'Whatsnew.txt';

        $result = $transfer->get($destination);

        $this->assertFalse($result);
    }

    public function testGetSuccessNoOverwrite()
    {
        $stream = new FakeFile();
        $this->_fileSystem->openFileFakeResult = $stream;
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $tempDestination = $destination . '.tmp';
        $contents = "This is the contents";
        $this->_curlApi->execFakeResult = $contents;
        $this->_curlApi->getinfoFakeResult = 200;
        $transfer = $this->createInstance($url);
        $this->_fileSystem->fileExistsFakeResult = false;

        $result = $transfer->get($destination);

        $this->assertTrue($result);
        $this->assertTrue($this->_fileSystem->openFileCalled);
        $this->assertEquals($tempDestination, $this->_fileSystem->openFileLastPath);
        $this->assertEquals('w', $this->_fileSystem->openFileLastMode);
        $this->assertTrue($stream->writeCalled);
        $this->assertEquals($contents, $stream->writeLastData);
        $this->assertTrue($stream->closeCalled);
        $this->assertTrue($this->_fileSystem->fileExistsCalled);
        $this->assertEquals($destination, $this->_fileSystem->fileExistsLastPath);
        $this->assertFalse($this->_fileSystem->unlinkCalled);
        $this->assertTrue($this->_fileSystem->renameCalled);
        $this->assertEquals($tempDestination, $this->_fileSystem->renameLastOldPath);
        $this->assertEquals($destination, $this->_fileSystem->renameLastNewPath);
    }

    public function testGetSuccessWithOverwrite()
    {
        $stream = new FakeFile();
        $this->_fileSystem->openFileFakeResult = $stream;
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $tempDestination = $destination . '.tmp';
        $contents = "This is the contents";
        $this->_curlApi->execFakeResult = $contents;
        $this->_curlApi->getinfoFakeResult = 200;
        $transfer = $this->createInstance($url);
        $this->_fileSystem->fileExistsFakeResult = true;

        $result = $transfer->get($destination);

        $this->assertTrue($result);
        $this->assertTrue($this->_fileSystem->openFileCalled);
        $this->assertEquals($tempDestination, $this->_fileSystem->openFileLastPath);
        $this->assertEquals('w', $this->_fileSystem->openFileLastMode);
        $this->assertTrue($this->_fileSystem->fileExistsCalled);
        $this->assertEquals($destination, $this->_fileSystem->fileExistsLastPath);
        $this->assertTrue($this->_fileSystem->unlinkCalled);
        $this->assertEquals($destination, $this->_fileSystem->unlinkLastPath);
        $this->assertTrue($this->_fileSystem->renameCalled);
        $this->assertEquals($tempDestination, $this->_fileSystem->renameLastOldPath);
        $this->assertEquals($destination, $this->_fileSystem->renameLastNewPath);
    }

    private $_curlApi;
    private $_fileSystem;
}
