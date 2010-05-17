<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'ProductionManx.php';
	require_once 'test/FakeDatabase.php';
	
	class TestManx extends PHPUnit_Framework_TestCase
	{
		public function testRenderDefaultCompanies()
		{
			$db = new FakeDatabase();
			$db->queryFakeResults = array(
				array('id' => 1, 'name' => 'DEC'),
				array('id' => 2, 'name' => '3Com'));
			$manx = ProductionManx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderDefaultCompanies();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals("SELECT `id`,`name` FROM `COMPANY` ORDER BY `sort_name`", $db->queryLastStatement);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<select id="CP" name="cp"><option value="1 selected>DEC</option><option value="2>3Com</option></select>', $output);
		}
	}
?>
