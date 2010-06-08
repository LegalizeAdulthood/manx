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
	protected function __construct($db)
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
	/*
		# Citations from other documents (only really important when there are no copies online)
		$sth = $dbh->prepare('select ph_company,ph_pub,ph_part,ph_title' .
			' from CITEPUB C' .
			' join PUB on (C.pub = pub_id and C.mentions_pub = ?)' .
			' join PUBHISTORY on pub_history = ph_id');
		$sth->execute($pub);
		my @citations;
		while (my $rc = $sth->fetchrow_hashref) {
			push @citations, format_doc_ref($rc); 
		}
		$sth->finish;
		if (scalar @citations) {
			print qq{<tr valign="top"><td>Cited by:</td><td><ul class="citelist"><li>}, join('</li><li>', @citations), qq{</li></ul></td></tr>\n};
		}
	*/
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

	public function renderTableOfContents($pubId)
	{
	/*
		my $started_contents = 0;

		$smt = 'select level,label,name from TOC where pub=?';
		if (!$full_contents) {
			$smt .= ' and level<2';
		}
		$smt .= ' order by line';
	
		$sth = $dbh->prepare($smt);
		warn $DBI::errstr if $DBI::err;
		$rv = $sth->execute($pub);
		warn $DBI::errstr if $DBI::err;

		my $currlevel = 0;
		while (@rowary = $sth->fetchrow_array) {
			if (!$started_contents) {
				if ($path_trump) {
					print qq{<H2>Table of Contents</H2>\n};
				} elsif ($full_contents) {
					param('cn','0');
					print qq{<H2>Full Contents [<A HREF="}, html_encode(self_url()), qq{">Mini</A>]</H2>\n};
				} else {
					param('cn','1');
					print qq{<H2>Mini Contents [<A HREF="}, html_encode(self_url()), qq{">Full</A>]</H2>\n};
				}
				print qq{<DIV CLASS="toc">};
				$started_contents = 1;
			}

			my ($rowlevel, $rowlabel, $rowname) = @rowary;
			if ($rowlevel > $currlevel) {
				++$currlevel;
				print "\n<UL>\n";
			} elsif ($rowlevel < $currlevel) {
				print "</LI>\n";
				while ($rowlevel < $currlevel) {
					print "</UL></LI>\n";
					--$currlevel;
				}
			} else {
				print "</LI>\n";
			}
			$rowlabel ||= '&nbsp;' if $currlevel > 1;
			print qq{<LI CLASS="level$currlevel"><SPAN}, ($currlevel == 1 ? ' CLASS="level1"' : ''), qq{>$rowlabel</SPAN> }, html_encode($rowname);
			
			#print "Row: $rowlevel, Label: $rowlabel, Name: $rowname<br>\n";
		}

		$rc = $sth->finish;
		warn $DBI::errstr if $DBI::err;

		if ($started_contents) {
			while (0 < $currlevel--) {
				print "</LI>\n</UL>";
			}
			print "</DIV>";
		}
	*/
	}
	
	public function renderCopies($pubId)
	{
	/*
		# COPIES
		#              0       1         2      3     4          5         6                 7               8         9
		$smt = 'select format, COPY.url, notes, size, SITE.name, SITE.url as site_url, SITE.description, SITE.copy_base, SITE.low, COPY.md5,' .
		#         10                 11
			' COPY.amend_serial, COPY.credits, copyid' .
			' from COPY,SITE where COPY.site=SITE.siteid and pub=? order by SITE.display_order,SITE.siteid';
		$sth = $dbh->prepare($smt);
		warn $DBI::errstr if $DBI::err;
		$rv = $sth->execute($pub);
		warn $DBI::errstr if $DBI::err;

		print "<h2>Copies</h2>\n";
		my $copy_count = 0;
		while (my $rcopy = $sth->fetchrow_hashref) {
			if (++$copy_count == 1) {
				print "<TABLE>\n<TBODY>";
			} else {
				print qq{<TR>\n<TD COLSPAN="2">&nbsp;</TD>\n</TR>\n};
			}

			my $copy_url;
			print "<TR>\n<TD>Address:</TD>\n<TD>";
			if ($rcopy->{url} =~ /^\+/) {
				$copy_url = $rcopy->{copy_base} . substr($rcopy->{url}, 1);
			} else {
				$copy_url = $rcopy->{url};
			}
			print qq{<A HREF="$copy_url">$copy_url</A></TD>\n</TR>\n};
			print qq{<TR>\n<TD>Site:</TD>\n<TD><A HREF="}, html_encode($rcopy->{site_url}), qq{">}, html_encode($rcopy->{description}),'</A>';
			print ' <SPAN CLASS="warning">(Low Bandwidth)</SPAN>' if $rcopy->{low} ne 'N';
			print qq{</TD>\n</TR>\n};
			print qq{<TR>\n<TD>Format:</TD>\n<TD>}, html_encode($rcopy->{format}), qq{</TD>\n</TR>\n};
			if ($rcopy->{size}) {
				print qq{<TR>\n<TD>Size:</TD>\n<TD>$rcopy->{size} bytes};
				my $size_mib = $rcopy->{size} / (1024 * 1024);
				my $size_kib = $rcopy->{size} / 1024;
				if ($size_mib > 1.0) {
					printf " (%.1f MiB)", $size_mib;
				} elsif ($size_kib > 1.0) {
					printf " (%.0f KiB)", $size_kib;
				}
				print qq{</TD>\n</TR>\n};
			}
			if ($rcopy->{md5}) {
				print qq{<TR>\n<TD>MD5:</TD>\n<TD>}, html_encode($rcopy->{md5}), qq{</TD>\n</TR>\n}; # shouldn't be anything to escape in md5!
			}
			if ($rcopy->{notes}) {
				print qq{<TR>\n<TD>Notes:</TD>\n<TD>}, html_encode($rcopy->{notes}), "</TD>\n</TR>\n";
			}
			if ($rcopy->{credits}) {
				print qq{<tr>\n<td>Credits:</td>\n<td>}, html_encode($rcopy->{credits}), "</td>\n</tr>\n";
			}
			if ($rcopy->{amend_serial}) {
				my $ramend = $dbh->selectrow_hashref('select ph_company,pub_id,ph_part,ph_title,ph_pubdate from PUB join PUBHISTORY on pub_history = ph_id where ph_amend_pub=? and ph_amend_serial=?', undef, $pub, $rcopy->{amend_serial});
				my $amend = qq{<a href="$DETAILSURL/$ramend->{ph_company},$ramend->{pub_id}"><cite>} . html_encode($ramend->{ph_title}) . qq{</cite></a>};
				$amend = html_encode($ramend->{ph_part}) . ', ' . $amend if defined($ramend->{ph_part});
				$amend .= ' (' . html_encode($ramend->{ph_pubdate}) . ')' if defined($ramend->{ph_pubdate});
				# Retrieve OS tags for amendments (see DEC-11-ORUGA-* for example)
				my $sthos = $dbh->prepare('select tag_text from TAG,PUBTAG where TAG.id=PUBTAG.tag and TAG.class="os" and pub=?');
				$sthos->execute($ramend->{pub_id});
				my (@rowtag, @tags);
				while (@rowtag = $sthos->fetchrow_array) {
					push @tags,$rowtag[0];
				}
				$sthos->finish;
				if (scalar @tags) {
					$amend .= ' <b>OS:</b> ' . html_encode(join(', ', @tags));
				}
				print qq{<tr>\n<td>Amended to:</td>\n<td>$amend</td>\n</tr>\n};
			}

			my $sthmirror = $dbh->prepare('select replace(url,original_stem,copy_stem) as mirror_url from COPY join mirror on COPY.site=mirror.site where copyid=? order by rank desc');
			$sthmirror->execute($rcopy->{copyid});
			my $mirror_count = 0;
			while (my $rmirror = $sthmirror->fetchrow_hashref) {
				if (++$mirror_count == 1) {
					print '<tr valign="top"><td>Mirrors:</td><td><ul style="list-style-type:none;margin:0;padding:0">';
				}
				print '<li style="margin:0;padding:0"><a href="', html_encode($rmirror->{mirror_url}), '">', html_encode($rmirror->{mirror_url}), '</a></li>';
			}
			$sthmirror->finish;
			if ($mirror_count > 0) {
				print '</ul></td></tr>';
			}
		}
		$rc = $sth->finish;
		warn $DBI::errstr if $DBI::err;

		if ($copy_count > 0) {
			print "</TBODY>\n</TABLE>\n";
		} else {
			print qq{<p>No copies known to be online. Please read the <a href="/manx/help#COPIES">Help</a> before emailing me about this.</p>};
		}
	*/
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
		$this->renderTableOfContents($pubId);
		$this->renderCopies($pubId);
		print "</div>\n";
	}
}

?>
