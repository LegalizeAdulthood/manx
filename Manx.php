<?php

require_once 'PDODatabaseAdapter.php';
require_once 'HtmlFormatter.php';
require_once 'ManxDatabase.php';
require_once 'Searcher.php';
require_once 'IManx.php';
require_once 'User.php';
require_once 'Cookie.php';

class Manx implements IManx
{
	private $_manxDb;

	public static function getInstance()
	{
		$db = PDODatabaseAdapter::getInstance();
		$manxDb = ManxDatabase::getInstanceForDatabase($db);
		return Manx::getInstanceForDatabase($manxDb);
	}
	public static function getInstanceForDatabase(IManxDatabase $db)
	{
		return new Manx($db);
	}
	protected function __construct($manxDb)
	{
		$this->_manxDb = $manxDb;
	}
	public function __destruct()
	{
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

	private function getRedirect($server)
	{
		$redirect = $server['PHP_SELF'];
		if (array_key_exists('QUERY_STRING', $server) and strlen($server['QUERY_STRING']) > 0)
		{
			$redirect = sprintf("%s?%s", $redirect, $server['QUERY_STRING']);
		}
		return urlencode($redirect);
	}

	function renderSearchResults()
	{
		$params = Searcher::parameterSource($_GET, $_POST);
		$searcher = Searcher::getInstance($this->_manxDb);
		print '<div id="Div1"><form action="search.php" method="get" name="f"><div class="field">Company: ';
		$company = (array_key_exists('cp', $params) ? $params['cp'] : 1);
		$keywords = urldecode(array_key_exists('q', $params) ? $params['q'] : '');
		$searcher->renderCompanies($company);
		print ' Keywords: <input id="q" name="q" size="20" maxlength="256" '
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

	private function renderOSTagsForPub($pubId)
	{
		$tags = $this->_manxDb->getOSTagsForPub($pubId);
		if (count($tags) > 0)
		{
			return ' <b>OS:</b> ' . htmlspecialchars(implode(', ', $tags));
		}
		return '';
	}

	private static function partPrefix($part)
	{
		$part = Manx::replaceNullWithEmptyStringOrTrim($part);
		if (strlen($part) > 0)
		{
			return htmlspecialchars($part) . ', ';
		}
		return '';
	}

	private static function formatPubDate($pubDate)
	{
		$pubDate = Manx::replaceNullWithEmptyStringOrTrim($pubDate);
		if (strlen($pubDate) > 0)
		{
			return ' (' . htmlspecialchars($pubDate) . ')';
		}
		return '';
	}

	public function renderAmendments($pubId)
	{
		$amendments = array();
		foreach ($this->_manxDb->getAmendmentsForPub($pubId) as $row)
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
		$tags = $this->_manxDb->getOSTagsForPub($pubId);
		if (count($tags) > 0)
		{
			echo '<tr><td>Operating System:</td><td>', htmlspecialchars(implode(', ', $tags)), "</td></tr>\n";
		}
	}

	public function renderLongDescription($pubId)
	{
		$startedDesc = false;
		foreach ($this->_manxDb->getLongDescriptionForPub($pubId) as $html)
		{
			if (!$startedDesc)
			{
				echo '<tr valign="top"><td>Description:</td><td>';
				$startedDesc = true;
			}
			print $html;
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
		foreach ($this->_manxDb->getCitationsForPub($pubId) as $row)
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
		// Supersession information. Because documents can be merged in later revisions,
		// or expand to become more than one, there may be more than one document that
		// preceded or superseded this one.
		$supers = array();
		foreach ($this->_manxDb->getPublicationsSupersededByPub($pubId) as $pub)
		{
			array_push($supers, $this->formatDocRef($pub));
		}
		if (count($supers) > 0)
		{
			echo '<tr valign="top"><td>Supersedes:</td><td><ul class="citelist"><li>',
				implode('</li><li>', $supers), "</li></ul></td></tr>\n";
		}
		$supers = array();
		foreach ($this->_manxDb->getPublicationsSupersedingPub($pubId) as $pub)
		{
			array_push($supers, $this->formatDocRef($pub));
		}
		if (count($supers) > 0)
		{
			echo '<tr valign="top"><td>Superseded by:</td><td><ul class="citelist"><li>',
				implode('</li><li>', $supers), "</li></ul></td></tr>\n";
		}
	}

	public function renderTableOfContents($pubId, $fullContents)
	{
		$currentLevel = 0;
		$startedContents = false;
		foreach ($this->_manxDb->getTableOfContentsForPub($pubId, $fullContents) as $row)
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
		print "<h2>Copies</h2>\n";
		$copyCount = 0;
		foreach ($this->_manxDb->getCopiesForPub($pubId) as $row)
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
			$md5 = Manx::replaceNullWithEmptyStringOrTrim($row['md5']);
			if (strlen($md5) > 0)
			{
				printf("<tr>\n<td>MD5:</td>\n<td>%s</td>\n</tr>\n", htmlspecialchars($md5));
			}
			$notes = Manx::replaceNullWithEmptyStringOrTrim($row['notes']);
			if (strlen($notes) > 0)
			{
				printf("<tr>\n<td>Notes:</td>\n<td>%s</td>\n</tr>\n", htmlspecialchars($notes));
			}
			$credits = Manx::replaceNullWithEmptyStringOrTrim($row['credits']);
			if (strlen($credits) > 0)
			{
				printf("<tr>\n<td>Credits:</td><td>%s</td>\n</tr>\n", htmlspecialchars($credits));
			}
			$amendSerial = $row['amend_serial'];
			if (!is_null($amendSerial))
			{
				$amendRow = $this->_manxDb->getAmendedPub($pubId, $amendSerial);
				$amend = sprintf("<a href=\"../details.php/%d,%d\"><cite>%s</cite></a>",
					$amendRow['ph_company'], $amendRow['pub_id'], htmlspecialchars($amendRow['ph_title']));
				$amend = Manx::partPrefix($amendRow['ph_part']) . $amend;
				$amend .= Manx::formatPubDate($amendRow['ph_pubdate']);
				$amend .= $this->renderOSTagsForPub($amendRow['pub_id']);
				printf("<tr>\n<td>Amended to:</td>\n<td>%s</td>\n</tr>\n", $amend);
			}

			$mirrorCount = 0;
			foreach ($this->_manxDb->getMirrorsForCopy($row['copyid']) as $mirror)
			{
				if (++$mirrorCount == 1)
				{
					print '<tr valign="top"><td>Mirrors:</td><td><ul style="list-style-type: none; margin: 0; padding: 0">';
				}
				printf("<li style=\"margin: 0; padding: 0\"><a href=\"%s\">%s</a></li>", $mirror, htmlspecialchars($mirror));
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

	function getDetailsForPathInfo($pathInfo)
	{
		$params = Manx::detailParamsForPathInfo($pathInfo);
		return array($params, $this->_manxDb->getDetailsForPub($params['id']));
	}

	function renderDetails($details)
	{
		$params = $details[0];
		$row = $details[1];
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
		$abstract = Manx::replaceNullWithEmptyStringOrTrim($row['ph_abstract']);
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

	public static function replaceNullWithEmptyStringOrTrim($value)
	{
		return is_null($value) ? '' : trim($value);
	}

	private function generateSessionId()
	{
		return sprintf("%s.%06d",
			strftime("%Y%m%d%H%M%S", gmmktime()),
			rand(0, 1000000));
	}

	function logout()
	{
		Cookie::delete();
		$this->_manxDb->deleteUserSession();
	}

	private function renderLoginLink($server)
	{
		$components = explode('/', $server['PHP_SELF']);
		$path = implode('/', array_slice($components, 0, count($components)-1));
		$port = $server['SERVER_PORT'];
		$port = ($port == '80') ? '' : ":" . $port;
		$redirect = $server['PHP_SELF'];
		if (array_key_exists('QUERY_STRING', $server) and strlen($server['QUERY_STRING']) > 0)
		{
			$redirect = sprintf("%s?%s", $redirect, $server['QUERY_STRING']);
		}
		printf('<a href="http://%s%s%s/login.php?redirect=%s">Login</a>',
			$server['SERVER_NAME'], $port, $path, urlencode($redirect));
	}

	public static function getRelativePrefixFromPathInfo()
	{
		return str_repeat('../', count(split('/', $_SERVER['PATH_INFO'])) - 1);
	}

	private function renderLogoutLink()
	{
		$prefix = $this->getRelativePrefixFromPathInfo();
		printf('<a href="%slogin.php?LOGO=1&redirect=%ssearch.php">Logout</a>', $prefix, $prefix);
	}

	function renderAuthorization()
	{
		$user = User::getInstanceFromSession($this->_manxDb);
		print '<div id="AUTH">' . $user->displayName() . ' | ';
		if ($user->isLoggedIn())
		{
			$this->renderLogoutLink();
		}
		else
		{
			$this->renderLoginLink($_SERVER);
		}
		print "</div>\n";
	}

	function loginUser($user, $password)
	{
		$userId = $this->_manxDb->getUserId($user, $password);
		if ($userId > 0)
		{
			$sessionId = $this->generateSessionId();
			$remoteHost = gethostbyaddr($_SERVER['REMOTE_ADDR']);
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
			$this->_manxDb->createSessionForUser($userId, $sessionId, $remoteHost, $userAgent);
			Cookie::set($sessionId);
			return true;
		}
		return false;
	}

}

?>
