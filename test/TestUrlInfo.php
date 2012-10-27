<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'test/FakeCurlApi.php';
require_once 'UrlInfo.php';

class TestUrlInfo extends PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$curlApi = new FakeCurlApi();
		$curl = new UrlInfo(null, $curlApi);
		$this->assertFalse($curlApi->initCalled);
		$this->assertNotNull($curl);
	}

	public function testSizeReturns4096()
	{
		$curlApi = new FakeCurlApi();
		$curlApi->initFakeResult = 0xdeadbeef;
		$curlApi->execFakeResult = "HTTP/1.0 200 OK\n"
			. "Content-Length: 4096\n"
			. "\n";
		$url = 'http://bitsavers.org/WhatsNew.txt';
		$curl = new UrlInfo($url, $curlApi);
		$size = $curl->size();
		$this->assertTrue($curlApi->initCalled);
		$this->assertTrue($curlApi->setoptCalled);
		$this->assertTrue($curlApi->execCalled);
		$this->assertTrue($curlApi->getinfoCalled);
		$this->assertTrue($curlApi->closeCalled);
		$this->assertEquals(4096, $size);
	}
}
