<?php

require_once 'PDODatabaseAdapter.php';
require_once 'Searcher.php';
require_once 'IManx.php';

class Manx implements IManx
{
	private $_db;

	public static function getInstance()
	{
		$config = explode(" ", trim(file_get_contents("config.txt")));
		$db = PDODatabaseAdapter::getInstance(new PDO($config[0], $config[1], $config[2]));
		return Manx::getInstanceForDatabase($db);
	}
	public static function getInstanceForDatabase($db)
	{
		return new Manx($db);
	}
	private function __construct($db)
	{
		$this->_db = $db;
	}
	public function __destruct()
	{
		$this->_db = null;
	}

	function renderSiteList()
	{
		try
		{
			print '<ul>';
			foreach ($this->_db->query("SELECT `url`,`description`,`low` FROM `SITE` WHERE `live`='Y' ORDER BY `siteid`") as $row)
			{
				print '<li><a href="' . $row['url'] . '">' . htmlspecialchars($row['description']) . '</a>';
				if ('Y' == $row['low'])
				{
					print ' <span class="warning">(Low Bandwidth)</span>';
				}
				print '</li>';
			}
			print '</ul>';
		}
		catch (Exception $e)
		{
			print "Unexpected error: " . $e->getMessage();
		}
	}
	function renderCompanyList()
	{
		try
		{
			$rows = $this->_db->query("SELECT COUNT(*) FROM `COMPANY` WHERE `display` = 'Y'")->fetch();
			$count = $rows[0];
			$i = 0;
			foreach ($this->_db->query("SELECT `id`,`name` FROM `COMPANY` WHERE `display` = 'Y' ORDER BY `sort_name`") as $row)
			{
				print '<a href="search.php?cp=' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</a>';
				$i++;
				if ($i < $count)
				{
					print ', ';
				}	
			}
		}
		catch (Exception $e)
		{
			print "Unexpected error: " . $e->getMessage();
		}
	}
	function renderDocumentSummary()
	{
		$rows = $this->_db->query("SELECT COUNT(*) FROM `PUB`")->fetch();
		print $rows[0] . ' manuals, ';
		$rows = $this->_db->query("SELECT COUNT(DISTINCT `pub`) FROM `COPY`")->fetch();
		print $rows[0] . ' of which are online, at ';
		$rows = $this->_db->query("SELECT COUNT(*) FROM `SITE`")->fetch();
		print $rows[0] . ' websites';
	}
	function renderLoginLink($page)
	{
		print '<a href="login.php?redirect=http%3A%2F%2Fvt100.net%2F' . $page . '">Login</a>';
	}
	
	function renderSearchResults()
	{
		$searcher = Searcher::getInstance($this->_db);
		print '<div id="Div1"><form action="search.php" method="get" name="f"><div class="field">Company: ';
		$params = Searcher::parameterSource($_GET, $_POST);
		$company = (array_key_exists($params, 'cp') ? $params['cp'] : 1);
		$keywords = urldecode(array_key_exists($params, 'q') ? $params['q'] : '');
		$searcher->renderCompanies($company);
		print 'Keywords: <input id="Text1" name="q" value="" size="20" maxlength="256" '
			. (array_key_exists($params, 'q') ? ' value="' . $keywords . '"' : '')
			. '/> '
			. 'Online only: <input type="checkbox" name="on" '
			. (array_key_exists($params, 'on') ? ' checked' : '')
			. '/> '
			. '<input id="Submit1" type="submit" value="Search" /></div></form></div>';
		$formatter = HtmlFormatter::getInstance();
		$online = array_key_exists($params, 'on') && ($params['on'] != '0');
		$searcher->renderSearchResults($formatter, $company, $keywords, $online);
	}
}

?>
