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
			$formatter->renderResultsBar(array(), array('graphics', 'terminal'), 0, 0, 1);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="resbar">Searching for "graphics" and "terminal". Results <b>1 - 1</b> of <b>1</b>.</div>',
				$output);
		}
		
		public function testRenderResultsBarIgnoredWordsOneResult()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderResultsBar(array('a', 'an', 'it'), array('graphics', 'terminal'), 0, 0, 1);
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
			$formatter->renderPageSelectionBar(1, 5, 10, array());
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>',
				$output);
		}
		
		public function testRenderPageSelectionBarPageOneOfTwo()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderPageSelectionBar(1, 20, 10, array('q' => 'vt220 terminal', 'cp' => 1));
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
				. '<a class="navpage" href="search.php?q=vt220+terminal;start=10;cp=1">2</a>&nbsp;&nbsp;'
				. '<a href="search.php?q=vt220+terminal;start=10;cp=1"><b>Next</b></a>'
				. '</div>',
				$output);
		}
		
		public function testRenderPageSelectionBarPageTwoOfThree()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderPageSelectionBar(11, 30, 10, array('q' => 'vt220 terminal', 'cp' => 1, 'start' => 10));
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;'
				. '<a href="search.php?q=vt220+terminal;start=0;cp=1"><b>Previous</b></a>&nbsp;&nbsp;'
				. '<a class="navpage" href="search.php?q=vt220+terminal;start=0;cp=1">1</a>&nbsp;&nbsp;'
				. '<b class="currpage">2</b>&nbsp;&nbsp;'
				. '<a class="navpage" href="search.php?q=vt220+terminal;start=20;cp=1">3</a>&nbsp;&nbsp;'
				. '<a href="search.php?q=vt220+terminal;start=20;cp=1"><b>Next</b></a></div>',
				$output);
		}
		
		public function testRenderPageSelectionBarPageThreeOfThree()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderPageSelectionBar(21, 30, 10, array('q' => 'vt100 terminal', 'cp' => 1, 'start' => 20));
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;'
				. '<a href="search.php?q=vt100+terminal;start=10;cp=1"><b>Previous</b></a>&nbsp;&nbsp;'
				. '<a class="navpage" href="search.php?q=vt100+terminal;start=0;cp=1">1</a>&nbsp;&nbsp;'
				. '<a class="navpage" href="search.php?q=vt100+terminal;start=10;cp=1">2</a>&nbsp;&nbsp;'
				. '<b class="currpage">3</b>&nbsp;&nbsp;</div>',
				$output);
		}
		
		public function testRenderPageSelectionBarPageOneOfTwoOnline()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderPageSelectionBar(1, 20, 10, array('q' => 'vt220 terminal', 'cp' => 1, 'on' => 'on'));
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
				. '<a class="navpage" href="search.php?q=vt220+terminal;start=10;on=on;cp=1">2</a>&nbsp;&nbsp;'
				. '<a href="search.php?q=vt220+terminal;start=10;on=on;cp=1"><b>Next</b></a>'
				. '</div>',
				$output);
		}
		
		public function testRenderPageSelectionBarPageOneOfTwoFivePerPage()
		{
			$formatter = HtmlFormatter::getInstance();
			ob_start();
			$formatter->renderPageSelectionBar(1, 10, 5, array('q' => 'vt220 terminal', 'cp' => 1, 'on' => 'on', 'num' => 5));
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
				. '<a class="navpage" href="search.php?q=vt220+terminal;start=5;num=5;on=on;cp=1">2</a>&nbsp;&nbsp;'
				. '<a href="search.php?q=vt220+terminal;start=5;num=5;on=on;cp=1"><b>Next</b></a>'
				. '</div>',
				$output);
		}
		
		private function createResultRowsForColumns($columns, $data)
		{
			$rows = array();
			foreach ($data as $item)
			{
				$row = array();
				for ($i = 0; $i < count($columns); $i++)
				{
					$row[$columns[$i]] = $item[$i];
				}
				array_push($rows, $row);
			}
			return $rows;
		}
		
		public function testRenderResultsPage()
		{
			$formatter = HtmlFormatter::getInstance();
			$rows = $this->createResultRowsForColumns(
				array('pub_id', 'ph_part', 'ph_title', 'pub_has_online_copies',
					'ph_abstract', 'pub_has_toc', 'pub_superseded', 'ph_pubdate',
					'ph_revision', 'ph_company', 'ph_alt_part', 'ph_pubtype', 'tags'),
				array(
					array(1, 'AA-4949A-TC', 'VT55 Programming Manual', 1, NULL, 1, 0, '1977-02', '', 1, NULL, 'D', array()),
					array(3014, 'EK-VT55E-TM-001', "VT55-D,E,H,J DECgraphic Scope Users' Manual", 1, NULL, 1, 0, '1976-12', '', 1, NULL, 'D', array()),
					array(9206, 'MP-00098-00', 'VT55 Field Maintenance Print Set', 0, NULL, 0, 0, NULL, '', 1, NULL, 'D', array())
					));
			ob_start();
			$formatter->renderResultsPage($rows, 0, 2);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<table class="restable">'
				. '<thead>'
					. '<tr><th>Part</th><th>Date</th><th>Title</th><th class="last">Status</th></tr>'
				. '</thead>'
				. '<tbody>'
				. '<tr valign="top">'
					. '<td>AA-4949A-TC</td>'
					. '<td>1977-02</td>'
					. '<td><a href="details.php/1,1">VT55 Programming Manual</a></td>'
					. '<td>Online, ToC</td>'
				. '</tr>'
				. '<tr valign="top">'
					. '<td>EK-VT55E-TM-001</td><td>1976-12</td>'
					. '<td><a href="details.php/1,3014">VT55-D,E,H,J DECgraphic Scope Users\' Manual</a></td>'
					. '<td>Online, ToC</td>'
				. '</tr>'
				. '<tr valign="top">'
					. '<td>MP-00098-00</td>'
					. '<td></td>'
					. '<td><a href="details.php/1,9206">VT55 Field Maintenance Print Set</a></td>'
					. '<td>&nbsp;</td>'
				. '</tr>'
				. '</tbody>'
				. '</table>',
				$output);
		}
	}
?>
