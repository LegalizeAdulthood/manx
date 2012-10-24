<?php

require_once 'PageBase.php';
require_once 'test/FakeDatabase.php';
require_once 'test/FakeManxDatabase.php';

class AdminPageBaseTester extends AdminPageBase
{
	public function param($name, $defaultValue = '')
	{
		return parent::param($name, $defaultValue);
	}

	protected function postPage()
	{
		throw new BadMethodCallException();
	}

	protected function renderBodyContent()
	{
		throw new BadMethodCallException();
	}
}

class TestAdminPageBase extends PHPUnit_Framework_TestCase
{
	public function testParamUrlWithoutPlusGivesUrl()
	{
		$manx = new FakeManx();
		$manx->getDatabaseFakeResult = new FakeManxDatabase();
		$url = 'http://foo';
		$page = new AdminPageBaseTester($manx, array('url' => rawurlencode($url)));
		$this->assertEquals($url, $page->param('url'));
	}

	public function testParamUrlWithPlusGivesUrl()
	{
		$manx = new FakeManx();
		$manx->getDatabaseFakeResult = new FakeManxDatabase();
		$url = 'http://foo/3+Open';
		$page = new AdminPageBaseTester($manx, array('url' => rawurlencode($url)));
		$this->assertEquals($url, $page->param('url'));
	}
}

?>
