<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

require_once __DIR__ . '/../vendor/autoload.php';

class PdfMetadataTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_parser = $this->createMock(Manx\IPdfMetadataParser::class);
        $this->_handler = new MockHandler();
        $this->_history = array();
        $handlerStack = HandlerStack::create($this->_handler);
        $handlerStack->push(Middleware::history($this->_history));
        $this->_client = new Client(array('handler' => $handlerStack));
        $this->_tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'manx-pdf-test-' . uniqid();
        mkdir($this->_tempDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->_tempDir . DIRECTORY_SEPARATOR . '*') as $fileName)
        {
            unlink($fileName);
        }
        rmdir($this->_tempDir);
    }

    public function testSmallPdfMetadata()
    {
        $url = 'http://bitsavers.org/pdf/foo.pdf';
        $metadata = array(
            'title' => 'Title',
            'keywords' => 'key words',
            'abstract' => 'Abstract',
            'copy_notes' => 'Notes',
            'copy_credits' => 'Credits'
        );
        $this->_db->expects($this->once())->method('getProperty')
            ->with(Manx\PdfMetadata::MAX_BYTES_PROPERTY)
            ->willReturn('16');
        $this->_handler->append(
            new Response(200, array('Content-Length' => '6')),
            new Response(200, array(), '123456'));
        $this->_parser->expects($this->once())->method('metadataForFile')
            ->willReturn($metadata);
        $pdfMetadata = $this->createInstance();

        $result = $pdfMetadata->metadataForUrl($url);

        $this->assertSame(array_merge(array('status' => 'ok'), $metadata), $result);
        $this->assertRequestMethods(array('HEAD', 'GET'));
        $this->assertTempDirEmpty();
    }

    public function testDefaultTempDirUsesPrivateDirectory()
    {
        $pdfMetadata = new Manx\PdfMetadata(
            $this->_db,
            $this->_parser,
            $this->_client);
        $tempDir = new ReflectionProperty(Manx\PdfMetadata::class, '_tempDir');
        $tempDir->setAccessible(true);
        $tempPrefix = new ReflectionProperty(Manx\PdfMetadata::class, '_tempPrefix');
        $tempPrefix->setAccessible(true);
        $privatePrefix = Manx\Config::configFile(Manx\PdfMetadata::TEMP_FILE_PREFIX);

        $this->assertSame(
            dirname($privatePrefix),
            $tempDir->getValue($pdfMetadata));
        $this->assertSame(
            basename($privatePrefix),
            $tempPrefix->getValue($pdfMetadata));
    }

    public function testNonPdfUrlDoesNotDownload()
    {
        $this->_db->expects($this->never())->method('getProperty');
        $this->_parser->expects($this->never())->method('metadataForFile');
        $pdfMetadata = $this->createInstance();

        $result = $pdfMetadata->metadataForUrl('http://bitsavers.org/pdf/foo.txt');

        $this->assertSame(Manx\PdfMetadata::emptyResult('not_pdf'), $result);
        $this->assertRequestMethods(array());
    }

    public function testMissingContentLengthDoesNotDownload()
    {
        $this->expectCap('16');
        $this->_handler->append(new Response(200));
        $this->_parser->expects($this->never())->method('metadataForFile');
        $pdfMetadata = $this->createInstance();

        $result = $pdfMetadata->metadataForUrl('http://bitsavers.org/pdf/foo.pdf');

        $this->assertSame(Manx\PdfMetadata::emptyResult('too_large'), $result);
        $this->assertRequestMethods(array('HEAD'));
    }

    public function testOverLimitContentLengthDoesNotDownload()
    {
        $this->expectCap('4');
        $this->_handler->append(new Response(200, array('Content-Length' => '5')));
        $this->_parser->expects($this->never())->method('metadataForFile');
        $pdfMetadata = $this->createInstance();

        $result = $pdfMetadata->metadataForUrl('http://bitsavers.org/pdf/foo.pdf');

        $this->assertSame(Manx\PdfMetadata::emptyResult('too_large'), $result);
        $this->assertRequestMethods(array('HEAD'));
    }

    public function testOverLimitDownloadStopsBeforeParse()
    {
        $this->expectCap('4');
        $this->_handler->append(
            new Response(200, array('Content-Length' => '4')),
            new Response(200, array(), '12345'));
        $this->_parser->expects($this->never())->method('metadataForFile');
        $pdfMetadata = $this->createInstance();

        $result = $pdfMetadata->metadataForUrl('http://bitsavers.org/pdf/foo.pdf');

        $this->assertSame(Manx\PdfMetadata::emptyResult('too_large'), $result);
        $this->assertRequestMethods(array('HEAD', 'GET'));
        $this->assertTempDirEmpty();
    }

    public function testEmptyParserResult()
    {
        $this->expectCap('16');
        $this->_handler->append(
            new Response(200, array('Content-Length' => '6')),
            new Response(200, array(), '123456'));
        $this->_parser->expects($this->once())->method('metadataForFile')
            ->willReturn(Manx\PdfMetadataParser::emptyMetadata());
        $pdfMetadata = $this->createInstance();

        $result = $pdfMetadata->metadataForUrl('http://bitsavers.org/pdf/foo.pdf');

        $this->assertSame(Manx\PdfMetadata::emptyResult('empty'), $result);
        $this->assertRequestMethods(array('HEAD', 'GET'));
        $this->assertTempDirEmpty();
    }

    private function createInstance()
    {
        return new Manx\PdfMetadata(
            $this->_db,
            $this->_parser,
            $this->_client,
            $this->_tempDir);
    }

    private function expectCap($value)
    {
        $this->_db->expects($this->once())->method('getProperty')
            ->with(Manx\PdfMetadata::MAX_BYTES_PROPERTY)
            ->willReturn($value);
    }

    private function assertRequestMethods(array $methods)
    {
        $actual = array();
        foreach ($this->_history as $transaction)
        {
            array_push($actual, $transaction['request']->getMethod());
        }
        $this->assertSame($methods, $actual);
    }

    private function assertTempDirEmpty()
    {
        $this->assertSame(array(), glob($this->_tempDir . DIRECTORY_SEPARATOR . '*'));
    }

    private $_client;
    private $_db;
    private $_handler;
    private $_history;
    private $_parser;
    private $_tempDir;
}
