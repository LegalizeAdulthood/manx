<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

require_once __DIR__ . '/../vendor/autoload.php';

class UrlTransferTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->_handler = new MockHandler();
        $this->_history = array();
        $handlerStack = HandlerStack::create($this->_handler);
        $handlerStack->push(Middleware::history($this->_history));
        $this->_client = new Client(array('handler' => $handlerStack));
        $this->_fileSystem = $this->createMock(Manx\IFileSystem::class);
        $this->_stream = null;
    }

    protected function tearDown(): void
    {
        if (is_resource($this->_stream))
        {
            fclose($this->_stream);
        }
    }

    private function createInstance($url)
    {
        return new Manx\UrlTransfer($url, $this->_client, $this->_fileSystem);
    }

    private function createFile($tempDestination)
    {
        $file = $this->createMock(Manx\IFile::class);
        $this->_stream = fopen('php://temp', 'w+');
        $this->_fileSystem->expects($this->once())->method('openFile')
            ->with($tempDestination, 'w')
            ->willReturn($file);
        $file->expects($this->once())->method('getStream')->willReturn($this->_stream);
        $file->expects($this->once())->method('close');
        return $file;
    }

    private function assertGetRequest($url)
    {
        $this->assertCount(1, $this->_history);
        $transaction = $this->_history[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals($url, (string)$transaction['request']->getUri());
        $this->assertSame($this->_stream, $transaction['options']['sink']);
        $this->assertFalse($transaction['options']['http_errors']);
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
        $destination = Manx\Config::configFile('Whatsnew.txt');
        $tempDestination = $destination . '.tmp';
        $this->createFile($tempDestination);
        $this->_handler->append(new Response(404));
        $this->_fileSystem->expects($this->never())->method('fileExists');
        $this->_fileSystem->expects($this->never())->method('unlink');
        $this->_fileSystem->expects($this->never())->method('rename');
        $transfer = $this->createInstance($url);

        $result = $transfer->get($destination);

        $this->assertGetRequest($url);
        $this->assertFalse($result);
    }

    public function testGetRequestFailure()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = Manx\Config::configFile('Whatsnew.txt');
        $tempDestination = $destination . '.tmp';
        $this->createFile($tempDestination);
        $this->_handler->append(new ConnectException('Could not connect', new Request('GET', $url)));
        $this->_fileSystem->expects($this->never())->method('fileExists');
        $this->_fileSystem->expects($this->never())->method('unlink');
        $this->_fileSystem->expects($this->never())->method('rename');
        $transfer = $this->createInstance($url);

        $result = $transfer->get($destination);

        $this->assertGetRequest($url);
        $this->assertFalse($result);
    }

    public function testGetSuccessNoOverwrite()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = Manx\Config::configFile('Whatsnew.txt');
        $tempDestination = $destination . '.tmp';
        $this->createFile($tempDestination);
        $this->_handler->append(new Response(200));
        $this->_fileSystem->expects($this->once())->method('fileExists')->with($destination)->willReturn(false);
        $this->_fileSystem->expects($this->never())->method('unlink');
        $this->_fileSystem->expects($this->once())->method('rename')->with($tempDestination, $destination);
        $transfer = $this->createInstance($url);

        $result = $transfer->get($destination);

        $this->assertGetRequest($url);
        $this->assertTrue($result);
    }

    public function testGetSuccessWithOverwrite()
    {
        $url = 'http://bitsavers.org/Whatsnew.txt';
        $destination = Manx\Config::configFile('Whatsnew.txt');
        $tempDestination = $destination . '.tmp';
        $this->createFile($tempDestination);
        $this->_handler->append(new Response(200));
        $this->_fileSystem->expects($this->once())->method('fileExists')->with($destination)->willReturn(true);
        $this->_fileSystem->expects($this->once())->method('unlink')->with($destination);
        $this->_fileSystem->expects($this->once())->method('rename')->with($tempDestination, $destination);
        $transfer = $this->createInstance($url);

        $result = $transfer->get($destination);

        $this->assertGetRequest($url);
        $this->assertTrue($result);
    }

    private $_client;
    private $_fileSystem;
    private $_handler;
    private $_history;
    private $_stream;
}
