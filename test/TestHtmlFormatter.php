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
			$formatter->renderResultsBar(array(), array(), 0, 0, 1);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="resbar">Showing all documents. Results <b>1 - 1</b> of <b>1</b>.</div>',
				$output);
		}
	}
?>
