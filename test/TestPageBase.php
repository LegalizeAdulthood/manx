<?php

require_once 'pages/PageBase.php';
require_once 'test/FakeDatabase.php';
require_once 'test/FakeManxDatabase.php';

class PageBaseTester extends PageBase
{
	protected function renderBodyContent()
	{
	}
}

class TestPageBase extends PHPUnit_Framework_TestCase
{
	private $_db;
	private $_manx;
	private $_page;

	private function fakeStatementFetchResults($results)
	{
		$stmt = new FakeStatement();
		$stmt->fetchFakeResult = $results;
		return $stmt;
	}

	private function createInstance()
	{
		$_SERVER['PATH_INFO'] = '';
		$this->_db = new FakeManxDatabase();
		$this->_manx = new FakeManx();
		$this->_manx->getDatabaseFakeResult = $this->_db;
		$this->_page = new PageBaseTester($this->_manx);
	}

	private function startOutputCapture()
	{
		ob_start();
	}

	private function finishOutputCapture()
	{
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public function testRenderLoginLink()
	{
		$this->createInstance();
		$this->startOutputCapture();
		$this->_page->renderLoginLink(array('PHP_SELF' => '/manx/about.php'));
		$output = $this->finishOutputCapture();
		$this->assertEquals('<a href="login.php?redirect=%2Fmanx%2Fabout.php">Login</a>', $output);
	}
}

?>
