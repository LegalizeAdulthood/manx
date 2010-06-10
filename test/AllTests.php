<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'test/TestManx.php';
	require_once 'test/TestSearcher.php';
	require_once 'test/TestHtmlFormatter.php';
	require_once 'test/TestManxDatabase.php';

	class AllTests
	{
		public static function suite()
		{
			$suite = new PHPUnit_Framework_TestSuite('ManxTests');
			$suite->addTestSuite('TestManx');
			$suite->addTestSuite('TestSearcher');
			$suite->addTestSuite('TestHtmlFormatter');
			$suite->addTestSuite('TestManxDatabase');
			return $suite;
		}
	}
?>
