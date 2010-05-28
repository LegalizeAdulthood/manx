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
		
		public function testRenderResultsBarAllDocumentsOneResult()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderResultsBar(array(), array(), 1, 1, 1);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="resbar">Showing all documents. Results <b>1 - 1</b> of <b>1</b>.</div>',
				$output);
		}
		
		public function testNeatQuotedListOneWord()
		{
			$this->assertEquals('"graphics"', HtmlFormatter::neatQuotedList(array('graphics')));
		}
		
		public function testNeatQuotedListTwoWords()
		{
			$this->assertEquals('"graphics" and "terminal"', HtmlFormatter::neatQuotedList(array('graphics', 'terminal')));
		}
		
		public function testNeatQuotedListThreeWords()
		{
			$this->assertEquals('"graphics", "terminal" and "serial"',
				HtmlFormatter::neatQuotedList(array('graphics', 'terminal', 'serial')));
		}
		
		public function testRenderResultsBarGraphicsTerminalOneResult()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderResultsBar(array(), array('graphics', 'terminal'), 1, 1, 1);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="resbar">Searching for "graphics" and "terminal". Results <b>1 - 1</b> of <b>1</b>.</div>',
				$output);
		}
	}
?>
