<?php

require_once 'test/TestAboutPage.php';
require_once 'test/TestDetailsPage.php';
require_once 'test/TestHtmlFormatter.php';
require_once 'test/TestManxDatabase.php';
require_once 'test/TestPageBase.php';
require_once 'test/TestRssPage.php';
require_once 'test/TestSearcher.php';
require_once 'test/TestUrlWizardPage.php';
require_once 'test/TestUrlWizardService.php';

class AllTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('ManxTests');
		$suite->addTestSuite('TestAboutPage');
		$suite->addTestSuite('TestDetailsPage');
		$suite->addTestSuite('TestHtmlFormatter');
		$suite->addTestSuite('TestManxDatabase');
		$suite->addTestSuite('TestPageBase');
		$suite->addTestSuite('TestRssPage');
		$suite->addTestSuite('TestSearcher');
		$suite->addTestSuite('TestUrlWizardPage');
		$suite->addTestSuite('TestUrlWizardService');
		return $suite;
	}
}

?>
