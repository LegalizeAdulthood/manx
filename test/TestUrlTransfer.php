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

        $transfer = $this->createInstance($url);

        $this->assertNotNull($transfer);
    }

    public function testGetFailure()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $this->_curlApi->execFakeResult = "";
        $this->_curlApi->getinfoFakeResult = 404;
        $transfer = $this->createInstance($url);

        $result = $transfer->get($destination);

        $this->assertFalse($result);
        $this->assertTrue($this->_curlApi->initCalled);
        $this->assertTrue($this->_curlApi->execCalled);
        $this->assertTrue($this->_curlApi->getinfoCalled);
        $this->assertTrue($this->_curlApi->closeCalled);
        $this->assertFalse($this->_fileSystem->openFileCalled);
    }

    public function testGetSuccessNoOverwrite()
    {
        $stream = new FakeFile();
        $this->_fileSystem->openFileFakeResult = $stream;
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $tempDestination = $destionation . '.tmp';
        $contents = "This is the contents";
        $this->_curlApi->execFakeResult = $contents;
        $this->_curlApi->getinfoFakeResult = 200;
        $transfer = $this->createInstance($url);
        $this->_fileSystem->fileExistsFakeResult = false;

        $result = $transfer->get($destination);

        $this->assertTrue($result);
        $this->assertTrue($this->_curlApi->initCalled);
        $this->assertTrue($this->_curlApi->execCalled);
        $this->assertTrue($this->_curlApi->getinfoCalled);
        $this->assertTrue($this->_curlApi->closeCalled);
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

    private $_curlApi;
    private $_fileSystem;
}
