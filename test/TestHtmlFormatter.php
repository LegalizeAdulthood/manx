<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'HtmlFormatter.php';

	class TestHtmlFormatter extends PHPUnit_Framework_TestCase
	{
		public function testConstruct()
		{
			$formatter = HtmlFormatter::getInstance();
			$this->assertTrue(is_object($formatter));
			$this->assertFalse(is_null($formatter));
		}	
	}
?>
