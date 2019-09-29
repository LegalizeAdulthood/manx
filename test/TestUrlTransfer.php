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
        $this->_fileFactory = new FakeFileFactory();
    }

    private function createInstance($url)
    {
        return new UrlTransfer($url, $this->_curlApi, $this->_fileFactory);
    }

    public function testConstruct()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';

        $transfer = $this->createInstance($url);

        $this->assertNotNull($transfer);
    }

    public function testGet()
    {
        $stream = new FakeFile();
        $this->_fileFactory->openFileFakeResult = $stream;
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = PRIVATE_DIR . 'Whatsnew.txt';
        $contents = "This is the contents";
        $this->_curlApi->execFakeResult = $contents;
        $transfer = $this->createInstance($url);

        $transfer->get($destination);

        $this->assertTrue($this->_curlApi->initCalled);
        $this->assertTrue($this->_curlApi->setoptCalled);
        $this->assertTrue($this->_curlApi->execCalled);
        $this->assertTrue($this->_curlApi->getinfoCalled);
        $this->assertTrue($this->_fileFactory->openFileCalled);
        $this->assertTrue($stream->writeCalled);
        $this->assertEqual($contents, $stream->writeLastData);
    }

    private $_curlApi;
    private $_fileFactory;
}
