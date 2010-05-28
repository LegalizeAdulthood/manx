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
		
		public function testRenderResultsBarSearchWordsOneResult()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderResultsBar(array(), array('graphics', 'terminal'), 1, 1, 1);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="resbar">Searching for "graphics" and "terminal". Results <b>1 - 1</b> of <b>1</b>.</div>',
				$output);
		}
		
		public function testRenderResultsBarIgnoredWordsOneResult()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderResultsBar(array('a', 'an', 'it'), array('graphics', 'terminal'), 1, 1, 1);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<p class="warning">Ignoring "a", "an" and "it".  All search words must be at least three letters long.</p>'
				. '<div class="resbar">Searching for "graphics" and "terminal". Results <b>1 - 1</b> of <b>1</b>.</div>',
				$output);
		}
		
		public function testRenderPageSelectionBarOnePage()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderPageSelectionBar(0, 5, 10);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>',
				$output);
		}
	}
?>
