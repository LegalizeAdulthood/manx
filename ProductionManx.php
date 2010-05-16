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
				print '<li><a href="' . $row['url'] . '">' . $row['description'] . '</a>';
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
				print '<a href="default.php?cp=' . $row['id'] . '">' . $row['name'] . '</a>';
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
		return '<select id="CP" name="cp"><OPTION VALUE="71">3Com Corporation</OPTION>
<OPTION VALUE="73">ABLE Communications</OPTION>
<OPTION VALUE="98">Acorn Computers Limited</OPTION>
<OPTION VALUE="53">Adaptec, Inc.</OPTION>
<OPTION VALUE="88">Adax, Inc.</OPTION>
<OPTION VALUE="66">Addmaster Corporation</OPTION>
<OPTION VALUE="87">Advanced Digital Corporation</OPTION>
<OPTION VALUE="56">Advanced Micro Devices</OPTION>
<OPTION VALUE="54">Advanced Scientific Instruments, Inc.</OPTION>
<OPTION VALUE="22">Alpha Microsystems</OPTION>
<OPTION VALUE="101">Altos Computer Systems</OPTION>
<OPTION VALUE="89">Ampro Computers, Inc.</OPTION>
<OPTION VALUE="75">Analogic Corporation</OPTION>
<OPTION VALUE="25">Apollo Computer, Inc.</OPTION>
<OPTION VALUE="46">Apple Computer</OPTION>
<OPTION VALUE="24">Applied Digital Data Systems</OPTION>
<OPTION VALUE="61">Applied Microsystems Corporation</OPTION>
<OPTION VALUE="62">Archive Corporation</OPTION>
<OPTION VALUE="90">Artec Electronics, Inc.</OPTION>
<OPTION VALUE="20">AT&amp;T Information Systems</OPTION>
<OPTION VALUE="74">Beehive International</OPTION>
<OPTION VALUE="91">Bondwell Holding Ltd.</OPTION>
<OPTION VALUE="21">Burroughs Corporation</OPTION>
<OPTION VALUE="8">C. Itoh Electronics</OPTION>
<OPTION VALUE="93">California Computer Products, Inc. (CalComp)</OPTION>
<OPTION VALUE="92">California Computer Systems</OPTION>
<OPTION VALUE="76">Central Data Corporation</OPTION>
<OPTION VALUE="17">Centronics</OPTION>
<OPTION VALUE="37">Centronics Data Computer Corp.</OPTION>
<OPTION VALUE="77">Century Data Systens</OPTION>
<OPTION VALUE="34">Citizen</OPTION>
<OPTION VALUE="60">CompuPro</OPTION>
<OPTION VALUE="72">Computer Control Company</OPTION>
<OPTION VALUE="94">Compu/Time</OPTION>
<OPTION VALUE="32">Control Data Corporation</OPTION>
<OPTION VALUE="63">Corvus Systems, Inc.</OPTION>
<OPTION VALUE="36">Cromemco, Inc.</OPTION>
<OPTION VALUE="67">Data Dynamics Ltd.</OPTION>
<OPTION VALUE="103">Data Electronics, Inc.</OPTION>
<OPTION VALUE="10">Data General Corporation</OPTION>
<OPTION VALUE="45">Data I/O Corporation</OPTION>
<OPTION VALUE="79">Data Systems Design, Inc.</OPTION>
<OPTION VALUE="18">Datapoint Corporation</OPTION>
<OPTION VALUE="38">Dataproducts Corporation</OPTION>
<OPTION VALUE="96">Diablo Systems, Inc.</OPTION>
<OPTION VALUE="1" SELECTED>Digital Equipment Corporation</OPTION>
<OPTION VALUE="44">Digital Research, Inc.</OPTION>
<OPTION VALUE="52">Digitronics Corporation</OPTION>
<OPTION VALUE="97">Distributed Logic Corporation (Dilog)</OPTION>
<OPTION VALUE="104">Documation, Inc.</OPTION>
<OPTION VALUE="95">Dual Systems Corporation</OPTION>
<OPTION VALUE="42">DY 4 Systems Inc.</OPTION>
<OPTION VALUE="64">Emulex Corporation</OPTION>
<OPTION VALUE="27">Epson</OPTION>
<OPTION VALUE="51">Facit</OPTION>
<OPTION VALUE="50">Ferranti Ltd.</OPTION>
<OPTION VALUE="59">John Fluke Mfg. Co., Inc.</OPTION>
<OPTION VALUE="80">GRI Computer Corporation</OPTION>
<OPTION VALUE="84">Hayes Microcomputer Products, Inc.</OPTION>
<OPTION VALUE="14">Hazeltine Corporation</OPTION>
<OPTION VALUE="12">Heath/Zenith</OPTION>
<OPTION VALUE="7">Hewlett-Packard</OPTION>
<OPTION VALUE="57">Honeywell Information Systems Inc.</OPTION>
<OPTION VALUE="65">INMOS Limited</OPTION>
<OPTION VALUE="47">Intel Corporation</OPTION>
<OPTION VALUE="58">Interdata/Perkin-Elmer</OPTION>
<OPTION VALUE="19">International Business Machines</OPTION>
<OPTION VALUE="100">International Computers and Tabulators Limited</OPTION>
<OPTION VALUE="83">Kimtron Corporation</OPTION>
<OPTION VALUE="4">A/S Kongsberg Våpenfabrikk</OPTION>
<OPTION VALUE="11">Lear Siegler, Inc.</OPTION>
<OPTION VALUE="81">Megatek Corporation</OPTION>
<OPTION VALUE="39">MITS, Inc.</OPTION>
<OPTION VALUE="33">Moore Corporation</OPTION>
<OPTION VALUE="68">Morrow Designs</OPTION>
<OPTION VALUE="49">Motorola</OPTION>
<OPTION VALUE="30">National Semiconductor Corporation</OPTION>
<OPTION VALUE="43">Nixdorf Computer Corporation</OPTION>
<OPTION VALUE="35">Oki Electric Industry Company, Ltd.</OPTION>
<OPTION VALUE="85">Osborne Computer Corporation</OPTION>
<OPTION VALUE="31">Prime Computer, Inc.</OPTION>
<OPTION VALUE="78">Qume Corporation</OPTION>
<OPTION VALUE="82">Random Corporation</OPTION>
<OPTION VALUE="15">Research, Inc.</OPTION>
<OPTION VALUE="3">Research Machines</OPTION>
<OPTION VALUE="29">S&amp;H Computer Systems, Inc.</OPTION>
<OPTION VALUE="86">Seattle Computer Products, Inc.</OPTION>
<OPTION VALUE="69">Shugart Associates</OPTION>
<OPTION VALUE="16">Soroc Technology, Inc.</OPTION>
<OPTION VALUE="26">Sun Microsystems, Inc.</OPTION>
<OPTION VALUE="28">Tandy Corporation</OPTION>
<OPTION VALUE="5">Tektronix</OPTION>
<OPTION VALUE="70">Teletype Corporation</OPTION>
<OPTION VALUE="6">TeleVideo</OPTION>
<OPTION VALUE="2">Texas Instruments</OPTION>
<OPTION VALUE="41">Vector Graphics, Inc.</OPTION>
<OPTION VALUE="9">Visual Technology Inc.</OPTION>
<OPTION VALUE="99">VLSI Technology, Inc.</OPTION>
<OPTION VALUE="55">Volker-Craig Ltd.</OPTION>
<OPTION VALUE="23">Western Digital Corporation</OPTION>
<OPTION VALUE="13">Wyse Technology</OPTION>
<OPTION VALUE="102">Xebec Systems, Inc.</OPTION>
<OPTION VALUE="48">Xerox Corporation</OPTION>
<OPTION VALUE="40">Zilog, Inc.</OPTION>
</SELECT>';
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
