<?php

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RedirectMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

class UrlInfoTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->_handler = new MockHandler();
        $this->_handlerStack = new HandlerStack($this->_handler);
        $this->_handlerStack->push(Middleware::redirect(), 'allow_redirects');
        $redirects = \GuzzleHttp\RedirectMiddleware::$defaultSettings;
        $redirects['track_redirects'] = true;
        $this->_client = new Client(['handler' => $this->_handlerStack, 'allow_redirects' => $redirects]);
        $this->_url = 'http://bitsavers.org/pdf/IndexByDate.txt';
        $this->_info = new Manx\UrlInfo($this->_url, $this->_client);
    }

    public function testSizeReturnsContentLength()
    {
        $response = new Response(200, ['Content-Length' => 4096]);
        $this->_handler->append($response);

        $size = $this->_info->size();

        $this->assertEquals(4096, $size);
    }

    public function testGetLastModified()
    {
        $response = new Response(200, ['Last-Modified' => 'Wed, 15 Nov 1995 04:58:08 GMT']);
        $this->_handler->append($response);

        $lastModified = $this->_info->lastModified();

        $this->assertEquals(strtotime('Wed, 15 Nov 1995 04:58:08 GMT'), $lastModified);
    }

    public function test404ErrorGivesSizeOfFalse()
    {
        $response = new Response(404);
        $this->_handler->append($response);

        $size = $this->_info->size();

        $this->assertTrue($size === false);
    }

    public function testExistsHttpStatus200()
    {
        $response = new Response(200);
        $this->_handler->append($response);

        $result = $this->_info->exists();

        $this->assertTrue($result);
    }

    public function testExistsHttpStatus404()
    {
        $response = new Response(404);
        $this->_handler->append($response);

        $result = $this->_info->exists();

        $this->assertFalse($result);
    }

    public function testExistsHttpStatus301()
    {
        $newUrl = 'http://other.org/pdf/IndexByDate.txt';
        $this->_handler->append(new Response(301, ['Location' => $newUrl]));
        $this->_handler->append(new Response(200));

        $result = $this->_info->exists();

        $this->assertEquals(0, $this->_handler->count());
        $this->assertTrue($result);
        $this->assertEquals($newUrl, $this->_info->url());
    }

    public function testExistsHttpStatus302()
    {
        $newUrl = 'http://other.org/pdf/IndexByDate.txt';
        $this->_handler->append(new Response(302, ['Location' => $newUrl]));
        $this->_handler->append(new Response(200));

        $result = $this->_info->exists();

        $this->assertEquals(0, $this->_handler->count());
        $this->assertTrue($result);
        $this->assertEquals($newUrl, $this->_info->url());
    }

    /** @var string */
    private $_url;
    /** @var GuzzleHttp\Handler\MockHandler */
    private $_handler;
    /** @var GuzzleHttp\Client */
    private $_client;
    /** @var Manx\UrlInfo */
    private $_info;
}
