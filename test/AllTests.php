<?php

require_once 'test/TestManx.php';
require_once 'test/TestSearcher.php';
require_once 'test/TestHtmlFormatter.php';
require_once 'test/TestManxDatabase.php';
require_once 'test/TestUrlWizardPage.php';
require_once 'test/TestUrlWizardService.php';
require_once 'test/TestAboutPage.php';
require_once 'test/TestPageBase.php';

class AllTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('ManxTests');
		$suite->addTestSuite('TestManx');
		$suite->addTestSuite('TestSearcher');
		$suite->addTestSuite('TestHtmlFormatter');
		$suite->addTestSuite('TestManxDatabase');
		$suite->addTestSuite('TestUrlWizardPage');
		$suite->addTestSuite('TestUrlWizardService');
		$suite->addTestSuite('TestAboutPage');
		$suite->addTestSuite('TestPageBase');
		return $suite;
	}
}

?>
