<?php

require_once 'PDODatabaseAdapter.php';
require_once 'HtmlFormatter.php';
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
		$params = Searcher::parameterSource($_GET, $_POST);
		$searcher = Searcher::getInstance($this->_db);
		print '<div id="Div1"><form action="search.php" method="get" name="f"><div class="field">Company: ';
		$company = (array_key_exists('cp', $params) ? $params['cp'] : 1);
		$keywords = urldecode(array_key_exists('q', $params) ? $params['q'] : '');
		$searcher->renderCompanies($company);
		print 'Keywords: <input id="q" name="q" size="20" maxlength="256" '
			. (array_key_exists('q', $params) ? ' value="' . $keywords . '"' : '')
			. '/> '
			. 'Online only: <input type="checkbox" name="on" '
			. (array_key_exists('on', $params) ? ' checked' : '')
			. '/> '
			. '<input id="Submit1" type="submit" value="Search" /></div></form></div>';
		$formatter = HtmlFormatter::getInstance();
		$online = array_key_exists('on', $params) && ($params['on'] != '0');
		$searcher->renderSearchResults($formatter, $company, $keywords, $online);
	}
	
	public static function detailParamsForPathInfo($pathInfo)
	{
		$matches = array();
		$params = array();
		if (1 == preg_match_all('/^\\/(\\d+),(\\d+)$/', $pathInfo, $matches))
		{
			$params['cp'] = $matches[1][0];
			$params['id'] = $matches[2][0];
			$params['cn'] = 1;
			$params['pn'] = 0;
		}
		return $params;
	}
	
	private function printTableRow($name, $value)
	{
		echo '<tr><td>', $name, ':</td><td>', htmlspecialchars(trim($value)), "</td></tr>\n";
	}
	
	private function printTableRowFromDatabaseRow($row, $name, $key)
	{
		$this->printTableRow($name, $row[$key]);
	}
	
	public static function neatListPlain($values)
	{
		if (count($values) > 1)
		{
			return implode(', ', array_slice($values, 0, count($values) - 1)) . ' and ' . $values[count($values) - 1];
		}
		else
		{
			return $values[0];
		}
	}
	
	public function renderLanguage($lang)
	{
		// TODO: the original Manx implementation relied on a table called LANGUAGE which doesn't
		// exist in the database dump.
		// Eventually, move this to a table, but for now use a hard coded list.
		if (!is_null($lang) && $lang != '+en')
		{
			$displayLanguage = array('en' => 'English', 'de' => 'German',
				'fr' => 'French', 'es' => 'Spanish', 'it' => 'Italian',
				'nl' => 'Dutch', 'no' => 'Norwegian', 'sv' => 'Swedish');
			$languages = array();
			foreach (explode('+', $lang) as $code)
			{
				if (count(trim($code)) > 0)
				{
					if (array_key_exists($code, $displayLanguage))
					{
						array_push($languages, trim($displayLanguage[$code]));
					}
				}
			}
			if (count($languages) > 0)
			{
				echo '<tr><td>Language', (count($languages) > 1) ? 's' : '', ':</td><td>', Manx::neatListPlain($languages), "</td></tr>\n";
			}			
		}
	}
	
	public function renderAmendments($pubId)
	{
		$amendments = array();
		$rows = $this->_db->query(sprintf("SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`,`ph_pubdate` "
			. "FROM `PUB` JOIN `PUBHISTORY` ON `pub_id` = `ph_pub` WHERE `ph_amend_pub`=%d ORDER BY `ph_amend_serial`",
			$pubId))->fetchAll();
		foreach ($rows as $row)
		{
			$amend = sprintf('<a href="../details.php/%d,%d"><cite>%s</cite></a>', $row['ph_company'], $row['ph_pub'], htmlspecialchars($row['ph_title']));
			$part = $row['ph_part'];
			$part = is_null($part) ? '' : trim($part);
			if (strlen($part) > 0)
			{
				$amend = htmlspecialchars($part) . ', ' . $amend;
			}
			$pubDate = $row['ph_pubdate'];
			$pubDate = is_null($pubDate) ? '' : trim($pubDate);
			if (strlen($pubDate) > 0)
			{
				$amend .= ' (' . htmlspecialchars($pubDate) . ')';
			}
			$query = sprintf('SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` AND `TAG`.`class`="os" AND `pub`=%d', $pubId);
			$tags = array();
			foreach ($this->_db->query($query)->fetchAll() as $tagRow)
			{
				array_push($tags, trim($tagRow['tag_text']));
			}
			if (count($tags) > 0)
			{
				$amend .= ' <b>OS:</b> ' . htmlspecialchars(implode(', ', $tags));
			}
			array_push($amendments, $amend);
		}
		if (count($amendments) > 0)
		{
			echo '<tr valign="top"><td>Amended&nbsp;by:</td><td><ul class="citelist"><li>', implode('</li><li>', $amendments), "</li></ul></td></tr>\n";
		}
	}
	
	public function renderOSTags($pubId)
	{
		$tags = array();
		$query = sprintf("SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` AND `TAG`.`class`='os' AND `pub`=%d", $pubId);
		foreach ($this->_db->query($query)->fetchAll() as $row)
		{
			array_push($tags, $row['tag_text']);
		}
		if (count($tags) > 0)
		{
			echo '<tr><td>Operating System:</td><td>', htmlspecialchars(implode(', ', $tags)), "</td></tr>\n";
		}
	}
	
	public function renderLongDescription($pubId)
	{
		// The Manx database dump doesn't contain a table called "LONG_DESC"; so do nothing for now.
		return;

		$query = sprintf("SELECT 'html_text' FROM `LONG_DESC` WHERE `pub`=%d ORDER BY `line`", $pubId);
		$startedDesc = false;
		foreach ($this->_db->query($query)->fetchAll() as $row)
		{
			if (!$startedDesc)
			{
				echo '<tr valign="top"><td>Description:</td><td>';
				$startedDesc = true;
			}
			print $row['html_text'];
		}
		if ($startedDesc)
		{
			echo '</td></tr>';
		}
	}
	
	public function renderCitations($pubId)
	{
	}
	
	public function renderSupersessions($pubId)
	{
	}
	
	public function renderTableOfContents($pubId)
	{
	}
	
	public function renderCopies($pubId)
	{
	}
	
	function renderDetails($pathInfo)
	{
		$params = Manx::detailParamsForPathInfo($pathInfo);
		$query = sprintf('SELECT `pub_id`, `COMPANY`.`name`, '
				. 'IFNULL(`ph_part`, "") AS `ph_part`, `ph_pubdate`, '
				. '`ph_title`, `ph_abstract`, '
				. 'IFNULL(`ph_revision`, "") AS `ph_revision`, `ph_ocr_file`, '
				. '`ph_cover_image`, `ph_lang`, `ph_keywords` '
				. 'FROM `PUB` '
				. 'JOIN `PUBHISTORY` ON `pub_history`=`ph_id` '
				. 'JOIN `COMPANY` ON `ph_company`=`COMPANY`.`id` '
				. 'WHERE %s AND `pub_id`=%d',
			'1=1', $params['id']);
		$rows = $this->_db->query($query)->fetch();
		$row = $rows[0];
		echo '<div class="det"><h1>', $row['ph_title'], "</h1>\n";
		echo '<table><tbody>';
		$this->printTableRowFromDatabaseRow($row, 'Company', 'name');
		$this->printTableRow('Part', $row['ph_part'] . ' ' . $row['ph_revision']);
		$this->printTableRowFromDatabaseRow($row, 'Date', 'ph_pubdate');
		$this->printTableRowFromDatabaseRow($row, 'Keywords', 'ph_keywords');
		$this->renderLanguage($row['ph_lang']);
		$pubId = $row['pub_id'];
		$this->renderAmendments($pubId);
		$this->renderOSTags($pubId);
		$this->renderLongDescription($pubId);
		$this->renderCitations($pubId);
		$this->renderSupersessions($pubId);
		$this->renderTableOfContents($pubId);
		$this->renderCopies($pubId);
		echo '</tbody></table>';
		echo '</div>';
	}
}

?>
