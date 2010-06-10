<?php

require_once 'PDODatabaseAdapter.php';
require_once 'HtmlFormatter.php';
require_once 'ManxDatabase.php';
require_once 'Searcher.php';
require_once 'IManx.php';
require_once 'IDatabase.php';

class Manx implements IManx
{
	private $_db;
	private $_manxDb;

	public static function getInstance()
	{
		$config = explode(" ", trim(file_get_contents("config.txt")));
		$db = PDODatabaseAdapter::getInstance(new PDO($config[0], $config[1], $config[2]));
		return Manx::getInstanceForDatabase($db);
	}
	public static function getInstanceForDatabase(IDatabase $db)
	{
		return Manx::getInstanceForDatabases($db, ManxDatabase::getInstanceForDatabase($db));
	}
	public static function getInstanceForDatabases(IDatabase $db, IManxDatabase $manxDb)
	{
		return new Manx($db, $manxDb);
	}
	protected function __construct($db, $manxDb)
	{
		$this->_db = $db;
		$this->_manxDb = $manxDb;
	}
	public function __destruct()
	{
		$this->_db = null;
		$this->_manxDb = null;
	}

	function renderSiteList()
	{
		try
		{
			print '<ul>';
			foreach ($this->_manxDb->getSiteList() as $row)
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
			$rows = $this->_manxDb->getCompanyList();
			$count = count($rows);
			$i = 0;
			foreach ($rows as $row)
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
		echo $this->_manxDb->getDocumentCount(), ' manuals, ',
			$this->_manxDb->getOnlineDocumentCount(), ' of which are online, at ',
			$this->_manxDb->getSiteCount(), ' websites';
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
		if (!is_null($lang) && $lang != '+en')
		{
			$languages = array();
			foreach (array_slice(explode('+', $lang), 1) as $languageCode)
			{
				array_push($languages, $this->_manxDb->getDisplayLanguage($languageCode));
			}
			if (count($languages) > 0)
			{
				echo '<tr><td>Language', (count($languages) > 1) ? 's' : '', ':</td><td>', Manx::neatListPlain($languages), "</td></tr>\n";
			}
		}
	}

	private function getOSTagsForPub($pubId)
	{
		$query = sprintf("SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` AND `TAG`.`class`='os' AND `pub`=%d", $pubId);
		$tags = array();
		foreach ($this->_db->query($query)->fetchAll() as $tagRow)
		{
			array_push($tags, trim($tagRow['tag_text']));
		}
		return $tags;
	}
	
	private function renderOSTagsForPub($pubId)
	{
		$tags = $this->getOSTagsForPub($pubId);
		if (count($tags) > 0)
		{
			return ' <b>OS:</b> ' . htmlspecialchars(implode(', ', $tags));
		}
		return '';
	}
	
	private static function partPrefix($part)
	{
		$part = is_null($part) ? '' : trim($part);
		if (strlen($part) > 0)
		{
			return htmlspecialchars($part) . ', ';
		}
		return '';
	}
	
	private static function formatPubDate($pubDate)
	{
		$pubDate = is_null($pubDate) ? '' : trim($pubDate);
		if (strlen($pubDate) > 0)
		{
			return ' (' . htmlspecialchars($pubDate) . ')';
		}
		return '';
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
			$amend = Manx::partPrefix($row['ph_part']) . $amend;
			$amend .= Manx::formatPubDate($row['ph_pubdate']);
			$amend .= $this->renderOSTagsForPub($pubId);
			array_push($amendments, $amend);
		}
		if (count($amendments) > 0)
		{
			echo '<tr valign="top"><td>Amended&nbsp;by:</td><td><ul class="citelist"><li>', implode('</li><li>', $amendments), "</li></ul></td></tr>\n";
		}
	}

	public function renderOSTags($pubId)
	{
		$tags = $this->getOSTagsForPub($pubId);
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

	public static function formatDocRef($row)
	{
		$out = sprintf('<a href="../details.php/%d,%d"><cite>%s</cite></a>', $row['ph_company'], $row['ph_pub'], htmlspecialchars($row['ph_title']));
		return Manx::partPrefix($row['ph_part']) . $out;
	}

	public function renderCitations($pubId)
	{
		// Citations from other documents (only really important when there are no copies online)
		$citations = array();
		$query = sprintf("SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`"
			. " FROM `CITEPUB` `C`"
			. " JOIN `PUB` ON (`C`.`pub`=`pub_id` AND `C`.`mentions_pub`=%d)"
			. " JOIN `PUBHISTORY` ON `pub_history`=`ph_id`", $pubId);
		foreach ($this->_db->query($query)->fetchAll() as $row)
		{
			array_push($citations, Manx::formatDocRef($row));
		}
		if (count($citations) > 0)
		{
			echo '<tr valign="top"><td>Cited by:</td><td><ul class="citelist"><li>', implode('</li><li>', $citations), "</li></ul></td></tr>\n";
		}
	}

	public function renderSupersessions($pubId)
	{
	/*
		# Supersession information. Because documents can be merged in later revisions, or expand to become more than one, there may
		# be more than one document that preceded or superseded this one.
		my @supers;
		$sth = $dbh->prepare('select ph_company,ph_pub,ph_part,ph_title from SUPERSESSION' .
			' join PUB on (old_pub = pub_id and new_pub=?)' .
			' join PUBHISTORY on pub_history = ph_id');
		$sth->execute($pub);
		while (my $rs = $sth->fetchrow_hashref) {
			push @supers, format_doc_ref($rs);
		}
		$sth->finish;
		if (scalar @supers) {
			print qq{<tr valign="top"><td>Supersedes:</td><td><ul class="citelist">},
				(map {"<li>$_</li>"} @supers), qq{</ul></td></tr>\n};
		}

		@supers = ();
		$sth = $dbh->prepare('select ph_company,ph_pub,ph_part,ph_title from SUPERSESSION' .
			' join PUB on (new_pub = pub_id and old_pub = ?)' .
			' join PUBHISTORY on pub_history = ph_id');
		$sth->execute($pub);
		while (my $rs = $sth->fetchrow_hashref) {
			push @supers, format_doc_ref($rs);
		}
		$sth->finish;
		if (scalar @supers) {
			print qq{<tr valign="top"><td>Superseded by:</td><td><ul class="citelist">},
				(map {"<li>$_</li>"} @supers), qq{</ul></td></tr>\n};
		}
	*/
	}

	public function renderTableOfContents($pubId, $fullContents)
	{
		$query = sprintf("SELECT `level`,`label`,`name` FROM `TOC` WHERE `pub`=%d", $pubId);
		if (!$fullContents)
		{
			$query .= ' AND `level` < 2';
		}
		$query .= ' ORDER BY `line`';
		$currentLevel = 0;
		$startedContents = false;
		foreach ($this->_db->query($query)->fetchAll() as $row)
		{
			if (!$startedContents)
			{
				print "<h2>Table of Contents</h2>\n";
				print '<div class="toc">';
				$startedContents = true;
			}
			$rowLevel = $row['level'];
			$rowLabel = $row['label'];
			$rowName = $row['name'];
			if ($rowLevel > $currentLevel)
			{
				++$currentLevel;
				print "\n<ul>\n";
			}
			else if ($rowLevel < $currentLevel)
			{
				print "</li>\n";
				while ($rowLevel < $currentLevel)
				{
					print "</ul></li>\n";
					--$currentLevel;
				}
			}
			else
			{
				print "</li>\n";
			}
			if (is_null($rowLabel) && $currentLevel > 1)
			{
				$rowLabel = '&nbsp;';
			}
			printf('<li class="level%d"><span%s>%s</span> %s',
				$currentLevel, ($currentLevel == 1 ? ' class="level1"' : ''), $rowLabel, htmlspecialchars($rowName));
		}
		if ($startedContents)
		{
			while (0 < $currentLevel--)
			{
				print "</li>\n</ul>";
			}
			print '</div>';
		}
	}

	public function renderCopies($pubId)
	{
		$query = sprintf("SELECT `format`,`COPY`.`url`,`notes`,`size`,"
			. "`SITE`.`name`,`SITE`.`url` AS `site_url`,`SITE`.`description`,"
			. "`SITE`.`copy_base`,`SITE`.`low`,`COPY`.`md5`,`COPY`.`amend_serial`,"
			. "`COPY`.`credits`,`copyid`"
			. " FROM `COPY`,`SITE`"
			. " WHERE `COPY`.`site`=`SITE`.`siteid` AND PUB=%d"
			. " ORDER BY `SITE`.`display_order`,`SITE`.`siteid`", $pubId);
		print "<h2>Copies</h2>\n";
		$copyCount = 0;
		foreach ($this->_db->query($query)->fetchAll() as $row)
		{
			if (++$copyCount == 1)
			{
				print "<table>\n<tbody>";
			}
			else
			{
				print "<tr>\n<td colspan=\"2\">&nbsp;</td>\n</tr>\n";
			}

			print "<tr>\n<td>Address:</td>\n<td>";
			$copyUrl = $row['url'];
			if (substr($copyUrl, 0, 1) == '+')
			{
				$copyUrl = $row['copy_base'] . substr($copyUrl, 1);
			}
			printf("<a href=\"%s\">%s</a></td>\n</tr>\n", $copyUrl, $copyUrl);
			printf("<tr>\n<td>Site:</td>\n<td><a href=\"%s\">%s</a>", htmlspecialchars($row['site_url']), htmlspecialchars($row['description']));
			if ($row['low'] != 'N')
			{
				print ' <span class="warning">(Low Bandwidth)</span>';
			}
			print "</td>\n</tr>\n";
			printf("<tr>\n<td>Format:</td>\n<td>%s</td>\n</tr>\n", htmlspecialchars($row['format']));
			$size = $row['size'];
			if ($size > 0)
			{
				printf("<tr>\n<td>Size:</td>\n<td>%d bytes", $size);
				$sizeMegabytes = $size/(1024*1024);
				$sizeKilobytes = $size/1024;
				if ($sizeMegabytes > 1.0)
				{
					printf(" (%.1f MiB)", $sizeMegabytes);
				}
				else if ($sizeKilobytes > 1.0)
				{
					printf(" (%.0f KiB)", $sizeKilobytes);
				}
				print "</td>\n</tr>\n";
			}
			$md5 = $row['md5'];
			$md5 = is_null($md5) ? '' : trim($md5);
			if (strlen($md5) > 0)
			{
				printf("<tr>\n<td>MD5:</td>\n<td>%s</td>\n</tr>\n", htmlspecialchars($md5));
			}
			$notes = $row['notes'];
			$notes = is_null($notes) ? '' : trim($notes);
			if (strlen($notes) > 0)
			{
				printf("<tr>\n<td>Notes:</td>\n<td>%s</td>\n</tr>\n", htmlspecialchars($notes));
			}
			$credits = $row['credits'];
			$credits = is_null($credits) ? '' : trim($credits);
			if (strlen($credits) > 0)
			{
				printf("<tr>\n<td>Credits:</td><td>%s</td>\n</tr>\n", htmlspecialchars($credits));
			}
			$amendSerial = $row['amend_serial'];
			if (!is_null($amendSerial))
			{
				$amendQuery = sprintf("SELECT `ph_company`,`pub_id`,`ph_part`,`ph_title`,`ph_pubdate`"
						. " FROM `PUB` JOIN `PUBHISTORY` ON `pub_history`=`ph_id`"
						. " WHERE `ph_amend_pub`=%d AND `ph_amend_serial`=%d",
					$pubId, $amendSerial);
				$amendRows = $this->_db->query($amendQuery)->fetch();
				$amendRow = $amendRows[0];
				$amend = sprintf("<a href=\"../details.php/%d,%d\"><cite>%s</cite></a>",
					$amendRow['ph_company'], $amendRow['pub_id'], htmlspecialchars($amendRow['ph_title']));
				$amend = Manx::partPrefix($amendRow['ph_part']) . $amend;
				$amend .= Manx::formatPubDate($amendRow['ph_pubdate']);
				$amend .= $this->renderOSTagsForPub($amendRow['pub_id']);
				printf("<tr>\n<td>Amended to:</td>\n<td>%s</td>\n</tr>\n", $amend);
			}

			$mirrorQuery = sprintf("SELECT REPLACE(`url`,`original_stem`,`copy_stem`) AS `mirror_url`"
					. " FROM `COPY` JOIN `mirror` ON `COPY`.`site`=`mirror`.`site`"
					. " WHERE `copyid`=%d ORDER BY `rank` DESC'", $row['copyid']);
			$mirrorCount = 0;
			foreach ($this->_db->query($mirrorQuery)->fetchAll() as $mirrorRow)
			{
				if (++$mirrorCount == 1)
				{
					print '<tr valign="top"><td>Mirrors:</td><td><ul style="list-style-type: none; margin: 0; padding: 0">';
				}
				printf("<li style=\"margin: 0; padding: 0\"><a href=\"%s\">%s</a></li>", $mirrorRow['mirror_url'], htmlspecialchars($mirrorRow['mirror_url']));
			}
			if ($mirrorCount > 0)
			{
				print '</ul></td></tr>';
			}
		}
		if ($copyCount > 0)
		{
			print "</tbody>\n</table>\n";
		}
		else
		{
			print '<p>No copies are known to be online.  Please read the <a href="../help.php#COPIES">Help</a> before emailing the administrator.</p>';
		}
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
		$rows = $this->_db->query($query)->fetchAll();
		$row = $rows[0];
		$coverImage = $row['ph_cover_image'];
		if (!is_null($coverImage))
		{
			echo '<div style="float:right; margin: 10px"><img src="', urlencode($coverImage), '" alt="" /></div>';
		}
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
		$abstract = $row['ph_abstract'];
		$abstract = is_null($abstract) ? '' : trim($abstract);
		if (strlen($abstract) > 0)
		{
			$this->printTableRow('Text', $abstract);
		}
		echo "</tbody>\n</table>\n";
		$fullContents = array_key_exists('cn', $params) && ($params['cn'] == 1);
		$this->renderTableOfContents($pubId, $fullContents);
		$this->renderCopies($pubId);
		print "</div>\n";
	}
}

?>
