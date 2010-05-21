<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'test/TestManx.php';
	require_once 'test/TestSearcher.php';
	
	class AllTests
	{
		public static function suite()
		{
			$suite = new PHPUnit_Framework_TestSuite('Manx');
			$suite->addTestSuite('TestManx');
			$suite->addTestSuite('TestSearcher');
			return $suite;
		}
	}
?>
