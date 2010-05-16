<?php

require 'Manx.php';

class ProductionManx implements Manx
{
	private $_pdo;

	public function __construct()
	{
		$this->_pdo = new PDO("mysql:host=localhost;dbname=manx", "root", "spammer");
	}
	public function __destruct()
	{
		$this->_pdo = null;
	}
	
	function renderSiteList()
	{
		try
		{
			print '<ul>';
			foreach ($this->_pdo->query("SELECT `url`,`description`,`low` FROM `SITE` ORDER BY `siteid`") as $row)
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
			$rows = $this->_pdo->query("SELECT COUNT(*) FROM `COMPANY` WHERE `display` = 'Y'")->fetch();
			$count = $rows[0];
			$i = 0;
			foreach ($this->_pdo->query("SELECT `id`,`name` FROM `COMPANY` WHERE `display` = 'Y' ORDER BY `sort_name`") as $row)
			{
				print '<a href="default.php?cp=' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</a>';
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
		$rows = $this->_pdo->query("SELECT COUNT(*) FROM `PUB`")->fetch();
		print $rows[0] . ' manuals, ';
		$rows = $this->_pdo->query("SELECT COUNT(*) FROM `PUB` WHERE `pub_has_online_copies` = 1")->fetch();
		print $rows[0] . ' of which are online, at ';
		$rows = $this->_pdo->query("SELECT COUNT(*) FROM `SITE`")->fetch();
		print $rows[0] . ' websites';
	}
	function renderLoginLink($page)
	{
		return '<a href="login.php?redirect=http%3A%2F%2Fvt100.net%2F' . $page . '">Login</a>';
	}
	
	public function renderDefaultCompanies()
	{
		print '<select id="CP" name="cp">';
		$defaultId = 1; // Digital Equipment Corporation
		foreach ($this->_pdo->query("SELECT `id`,`name` FROM `COMPANY` ORDER BY `sort_name`") as $row)
		{
			$id = $row['id'];
			print '<option value="' . $id . ($id == $defaultId ? ' selected' : '') . '>' . htmlspecialchars($row['name']) . '</option>';
		}
		print '</select>';
	}
	
	public function renderDefaultSearchResults()
	{
		return '<DIV CLASS="resbar">Showing all documents. Results <B>1 - 10</B> of <B>9688</B>.</DIV>
<DIV CLASS="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="currpage">1</B>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=10;cp=1">2</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=20;cp=1">3</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=30;cp=1">4</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=40;cp=1">5</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=50;cp=1">6</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=60;cp=1">7</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=70;cp=1">8</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=80;cp=1">9</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=90;cp=1">10</A>&nbsp;&nbsp;<A HREF="default.php?q=;start=10;cp=1"><B>Next</B></A></DIV>
<TABLE CLASS="restable"><THEAD><TR><TH>Part</TH><TH>Date</TH><TH>Title</TH><TH CLASS="last">Status</TH></TR></THEAD><TBODY><tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,3129">PDP-11/70 Hardware Student Handouts</A></TD>
<TD>Online</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,3230">FP11-B Floating-Point Processor Engineering Drawings</A></TD>
<TD>Online</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,3311">XVM Upgrades</A></TD>
<TD>Online</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,3329">RL11/01 Disk Sub-System Training Handout</A></TD>
<TD>Online</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,3874">Prioris MX 6200 Server-Specific Information</A></TD>
<TD>Online</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,3875">Prioris MX 6000 Servers Product Change Information</A></TD>
<TD>Online</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,3953">DW08 Schematics</A></TD>
<TD>Online</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,3999">TU77 Field Change Orders (FCOs)</A></TD>
<TD>Online</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,4300">KP8/I Power Failure Option Function Description</A></TD>
<TD>&nbsp;</TD>
</TR>
<tr valign="top">
<TD></TD>
<TD></TD>
<TD><A HREF="details.php/1,4301">KA8/I Positive I/O Bus Option Description</A></TD>
<TD>&nbsp;</TD>
</TR>
</TBODY></TABLE><DIV CLASS="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<B CLASS="currpage">1</B>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=10;cp=1">2</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=20;cp=1">3</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=30;cp=1">4</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=40;cp=1">5</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=50;cp=1">6</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=60;cp=1">7</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=70;cp=1">8</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=80;cp=1">9</A>&nbsp;&nbsp;<A CLASS="navpage" HREF="default.php?q=;start=90;cp=1">10</A>&nbsp;&nbsp;<A HREF="default.php?q=;start=10;cp=1"><B>Next</B></A></DIV>';
	}
}

?>
