<?php

require_once 'PDODatabaseAdapter.php';
require_once 'IManx.php';

class ProductionManx implements IManx
{
	private $_db;

	public static function getInstance()
	{
		$config = explode(" ", trim(file_get_contents("config.txt")));
		$db = PDODatabaseAdapter::getInstance(new PDO($config[0], $config[1], $config[2]));
		return ProductionManx::getInstanceForDatabase($db);
	}
	public static function getInstanceForDatabase($db)
	{
		return new ProductionManx($db);
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
			foreach ($this->_db->query("SELECT `url`,`description`,`low` FROM `SITE` ORDER BY `siteid`") as $row)
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
	
	public function renderDefaultCompanies()
	{
		print '<select id="CP" name="cp">';
		$defaultId = 1; // Digital Equipment Corporation
		foreach ($this->_db->query("SELECT `id`,`name` FROM `COMPANY` ORDER BY `sort_name`") as $row)
		{
			$id = $row['id'];
			print '<option value="' . $id . ($id == $defaultId ? ' selected' : '') . '>' . htmlspecialchars($row['name']) . '</option>';
		}
		print '</select>';
	}
	
	public function renderDefaultSearchResults()
	{
		return '<div class="resbar">Showing all documents. Results <b>1 - 10</b> of <b>9688</b>.</div>
<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=10;cp=1">2</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=20;cp=1">3</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=30;cp=1">4</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=40;cp=1">5</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=50;cp=1">6</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=60;cp=1">7</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=70;cp=1">8</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=80;cp=1">9</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=90;cp=1">10</a>&nbsp;&nbsp;<a href="default.php?q=;start=10;cp=1"><b>Next</b></a></div>
<table class="restable"><thead><tr><th>Part</th><th>Date</th><th>Title</th><th class="last">Status</th></tr></thead><tbody><tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,3129">PDP-11/70 Hardware Student Handouts</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,3230">FP11-B Floating-Point Processor Engineering Drawings</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,3311">XVM Upgrades</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,3329">RL11/01 Disk Sub-System Training Handout</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,3874">Prioris MX 6200 Server-Specific Information</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,3875">Prioris MX 6000 Servers Product Change Information</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,3953">DW08 Schematics</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,3999">TU77 Field Change Orders (FCOs)</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,4300">KP8/I Power Failure Option Function Description</a></td>
<td>&nbsp;</td>
</tr>
<tr valign="top">
<td></td>
<td></td>
<td><a href="details.php/1,4301">KA8/I Positive I/O Bus Option Description</a></td>
<td>&nbsp;</td>
</tr>
</tbody></table><div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=10;cp=1">2</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=20;cp=1">3</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=30;cp=1">4</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=40;cp=1">5</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=50;cp=1">6</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=60;cp=1">7</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=70;cp=1">8</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=80;cp=1">9</a>&nbsp;&nbsp;<a class="navpage" href="default.php?q=;start=90;cp=1">10</a>&nbsp;&nbsp;<a href="default.php?q=;start=10;cp=1"><b>Next</b></a></div>';
	}

	function renderSearchResults()
	{
		print '<div id="Div1"><form action="default.php" method="get" name="f"><div class="field">Company: ';
		$this->renderDefaultCompanies();
		print 'Keywords: <input id="Text1" name="q" value="" size="20" maxlength="256" />
Online only: <input type="checkbox" name="on" />
<input id="Submit1" type="submit" value="Search" /></div></form></div>';
		print $this->renderDefaultSearchResults();
	}
}

?>
