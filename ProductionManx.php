<?php

require 'Manx.php';

class ProductionManx implements Manx
{
	public function __construct()
	{
		$_pdo = new PDO("mysql:host=localhost;dbname=manx");
	}
	public function __destruct()
	{
		$_pdo = null;
	}
	
	function renderSiteList()
	{
		return '<ul>
<li><a href="http://vt100.net/">Paul Williams\' VT100.net</a></li>
<li><a href="http://www.computer.museum.uq.edu.au/">Wilber Williams\' Computer Museum</a></li>
<li><a href="http://bitsavers.org/">Al Kossow\'s Bitsavers</a></li>
<li><a href="http://pdp-8.org/">Aaron Nabil\'s PDP-8.org, a LINC, PDP-5, PDP-8 and PDP-12 resource</a></li>
<li><a href="http://highgate.comm.sfu.ca/pdp8/">Kevin McQuiggin\'s PDP-8 page at Highgate</a></li>
<li><a href="http://www.mainecoon.com/classiccmp/">Chris Kennedy\'s archive of Henk Gooijen\'s scans</a></li>
<li><a href="http://www.36bit.org/">Eric Smith\'s 36bit.org</a></li>
<li><a href="http://www.montagar.com/~patj/dec/hcps.htm">The DFWCUG Historical CPU Preservation Society</a></li>
<li><a href="http://scandocs.trailing-edge.com/">Tim Shoppa\'s Trailing Edge</a></li>
<li><a href="http://computer-refuge.org/">Patrick Finnegan\'s Computer Refuge</a></li>
<li><a href="http://deathrow.vistech.net/~cvisors/DEC94MDS/">Ivy\'s MDS Mirror</a> <span class="warning">(Low Bandwidth)</span></li>
<li><a href="http://cmcnabb.cc.vt.edu/dec94mds/">Christopher McNabb\'s MDS Mirror</a></li>
<li><a href="http://www.pdp8.net/">David Gesswein\'s PDP8.net</a> <span class="warning">(Low Bandwidth)</span></li>
<li><a href="http://www.xenya.si/">www.xenya.si</a></li>
<li><a href="http://www.dbit.com/">John Wilson\'s D Bit, Home of Ersatz-11</a></li>
<li><a href="http://users.rcn.com/crfriend/museum/index.shtml">Carl Friend\'s Minicomputer &quot;Museum&quot;</a></li>
<li><a href="http://research.microsoft.com/users/GBell/Digital/DECMuseum.htm">Gordon Bell\'s CyberMuseum for Digital Equipment Corporation</a></li>
<li><a href="http://highgate.comm.sfu.ca/~djg/htdocs/index.shtml">David Gesswein\'s Mirror of PDP8.net at Highgate</a></li>
<li><a href="http://zane.brouhaha.com/healyzh/museum.html">Zane Healy\'s Computer Museum</a></li>
<li><a href="http://dundas-mac.caltech.edu/~dundas/retro/">John\'s Retro Computing Notes</a></li>
<li><a href="http://cpp.seriousassault.de/">Sebastian Brückner\'s site</a></li>
<li><a href="http://www.jfc.org.uk/">James Carter\'s site</a></li>
<li><a href="http://www.vaxarchive.org/">Kees Stravers\' VAXarchive</a></li>
<li><a href="http://decstation.unix-ag.org/">DECstation Linux</a></li>
<li><a href="http://www.dadaboom.com/pdp11/">Bill King\'s PDP-11 Documentation</a></li>
<li><a href="http://h18002.www1.hp.com/">Hewlett-Packard Company</a></li>
<li><a href="http://www.tmk.com/ftp/">tmk.com software archive</a></li>
<li><a href="http://www.chd.dyndns.org/">CHD</a></li>
<li><a href="ftp://ftp.update.uu.se/">Update Computer Club FTP archives</a></li>
<li><a href="http://www.smecc.org/">Southwest Museum of Engineering, Communications and Computation</a></li>
<li><a href="http://www.update.uu.se/%7Ebqt/computers.html">Johnny Billquist\'s old computers</a></li>
<li><a href="http://www.otterway.com/am100/">Mike Noel\'s Virtual Alpha Micro</a> <span class="warning">(Low Bandwidth)</span></li>
<li><a href="http://www.dnpg.com/">Digital Networks</a></li>
<li><a href="http://www.cs.virginia.edu/brochure/images/manuals/">Historical Computer Literature at the University of Virginia</a></li>
<li><a href="http://www.aceware.iinet.net.au/acms/default.htm">The Australian Computer Museum Society</a></li>
<li><a href="http://elvira.stacken.kth.se/">ELVIRA</a></li>
<li><a href="http://www.sun.com/">Sun Microsystems</a></li>
<li><a href="http://bitchin100.com/">bitchin100.com</a></li>
<li><a href="http://www.hartetechnologies.com/manuals/">Howard M. Harte\'s S-100 Archive</a></li>
<li><a href="http://www.series80.org/">Vassilis Prevelakis\'s HP Series 80 site</a></li>
<li><a href="http://www.ba23.org/">Bridlewood Software Testers\' Guild</a></li>
<li><a href="http://www.classiccmp.org/dunfield/">Dave\'s Old Computers</a></li>
<li><a href="http://oldcomputers.dyndns.org/">Fritz Chwolka\'s archive</a></li>
<li><a href="http://vt100.net/mirror/harte/">Harte Mirror at VT100.net</a></li>
<li><a href="http://www.ibm1130.net/">Howard Shubs\' IBM 1130 site</a></li>
<li><a href="http://www.wotug.org/">WoTUG ? The Place for Communicating Processes</a></li>
<li><a href="http://www.xmission.com/~legalize/vintage/">Richard Thomson\'s Vintage Computer Collection</a></li>
<li><a href="http://neurosis.hungry.com/~ben/">Ben Mesander\'s site</a></li>
</ul>';
	}
	function renderCompanyList()
	{
		return '<A HREF="default.php?cp=71">3Com Corporation</A>, <A HREF="default.php?cp=73">ABLE Communications</A>, <A HREF="default.php?cp=98">Acorn Computers Limited</A>, <A HREF="default.php?cp=53">Adaptec, Inc.</A>, <A HREF="default.php?cp=88">Adax, Inc.</A>, <A HREF="default.php?cp=66">Addmaster Corporation</A>, <A HREF="default.php?cp=87">Advanced Digital Corporation</A>, <A HREF="default.php?cp=56">Advanced Micro Devices</A>, <A HREF="default.php?cp=54">Advanced Scientific Instruments, Inc.</A>, <A HREF="default.php?cp=22">Alpha Microsystems</A>, <A HREF="default.php?cp=101">Altos Computer Systems</A>, <A HREF="default.php?cp=89">Ampro Computers, Inc.</A>, <A HREF="default.php?cp=75">Analogic Corporation</A>, <A HREF="default.php?cp=25">Apollo Computer, Inc.</A>, <A HREF="default.php?cp=46">Apple Computer</A>, <A HREF="default.php?cp=24">Applied Digital Data Systems</A>, <A HREF="default.php?cp=61">Applied Microsystems Corporation</A>, <A HREF="default.php?cp=62">Archive Corporation</A>, <A HREF="default.php?cp=90">Artec Electronics, Inc.</A>, <A HREF="default.php?cp=20">AT&amp;T Information Systems</A>, <A HREF="default.php?cp=74">Beehive International</A>, <A HREF="default.php?cp=91">Bondwell Holding Ltd.</A>, <A HREF="default.php?cp=21">Burroughs Corporation</A>, <A HREF="default.php?cp=8">C. Itoh Electronics</A>, <A HREF="default.php?cp=93">California Computer Products, Inc. (CalComp)</A>, <A HREF="default.php?cp=92">California Computer Systems</A>, <A HREF="default.php?cp=76">Central Data Corporation</A>, <A HREF="default.php?cp=17">Centronics</A>, <A HREF="default.php?cp=37">Centronics Data Computer Corp.</A>, <A HREF="default.php?cp=77">Century Data Systens</A>, <A HREF="default.php?cp=34">Citizen</A>, <A HREF="default.php?cp=60">CompuPro</A>, <A HREF="default.php?cp=72">Computer Control Company</A>, <A HREF="default.php?cp=94">Compu/Time</A>, <A HREF="default.php?cp=32">Control Data Corporation</A>, <A HREF="default.php?cp=63">Corvus Systems, Inc.</A>, <A HREF="default.php?cp=36">Cromemco, Inc.</A>, <A HREF="default.php?cp=67">Data Dynamics Ltd.</A>, <A HREF="default.php?cp=103">Data Electronics, Inc.</A>, <A HREF="default.php?cp=10">Data General Corporation</A>, <A HREF="default.php?cp=45">Data I/O Corporation</A>, <A HREF="default.php?cp=79">Data Systems Design, Inc.</A>, <A HREF="default.php?cp=18">Datapoint Corporation</A>, <A HREF="default.php?cp=38">Dataproducts Corporation</A>, <A HREF="default.php?cp=96">Diablo Systems, Inc.</A>, <A HREF="default.php?cp=1">Digital Equipment Corporation</A>, <A HREF="default.php?cp=44">Digital Research, Inc.</A>, <A HREF="default.php?cp=52">Digitronics Corporation</A>, <A HREF="default.php?cp=97">Distributed Logic Corporation (Dilog)</A>, <A HREF="default.php?cp=104">Documation, Inc.</A>, <A HREF="default.php?cp=95">Dual Systems Corporation</A>, <A HREF="default.php?cp=42">DY 4 Systems Inc.</A>, <A HREF="default.php?cp=64">Emulex Corporation</A>, <A HREF="default.php?cp=27">Epson</A>, <A HREF="default.php?cp=51">Facit</A>, <A HREF="default.php?cp=50">Ferranti Ltd.</A>, <A HREF="default.php?cp=59">John Fluke Mfg. Co., Inc.</A>, <A HREF="default.php?cp=80">GRI Computer Corporation</A>, <A HREF="default.php?cp=84">Hayes Microcomputer Products, Inc.</A>, <A HREF="default.php?cp=14">Hazeltine Corporation</A>, <A HREF="default.php?cp=12">Heath/Zenith</A>, <A HREF="default.php?cp=7">Hewlett-Packard</A>, <A HREF="default.php?cp=57">Honeywell Information Systems Inc.</A>, <A HREF="default.php?cp=65">INMOS Limited</A>, <A HREF="default.php?cp=47">Intel Corporation</A>, <A HREF="default.php?cp=58">Interdata/Perkin-Elmer</A>, <A HREF="default.php?cp=19">International Business Machines</A>, <A HREF="default.php?cp=100">International Computers and Tabulators Limited</A>, <A HREF="default.php?cp=83">Kimtron Corporation</A>, <A HREF="default.php?cp=4">A/S Kongsberg Våpenfabrikk</A>, <A HREF="default.php?cp=11">Lear Siegler, Inc.</A>, <A HREF="default.php?cp=81">Megatek Corporation</A>, <A HREF="default.php?cp=39">MITS, Inc.</A>, <A HREF="default.php?cp=33">Moore Corporation</A>, <A HREF="default.php?cp=68">Morrow Designs</A>, <A HREF="default.php?cp=49">Motorola</A>, <A HREF="default.php?cp=30">National Semiconductor Corporation</A>, <A HREF="default.php?cp=43">Nixdorf Computer Corporation</A>, <A HREF="default.php?cp=35">Oki Electric Industry Company, Ltd.</A>, <A HREF="default.php?cp=85">Osborne Computer Corporation</A>, <A HREF="default.php?cp=31">Prime Computer, Inc.</A>, <A HREF="default.php?cp=78">Qume Corporation</A>, <A HREF="default.php?cp=82">Random Corporation</A>, <A HREF="default.php?cp=15">Research, Inc.</A>, <A HREF="default.php?cp=3">Research Machines</A>, <A HREF="default.php?cp=29">S&amp;H Computer Systems, Inc.</A>, <A HREF="default.php?cp=86">Seattle Computer Products, Inc.</A>, <A HREF="default.php?cp=69">Shugart Associates</A>, <A HREF="default.php?cp=16">Soroc Technology, Inc.</A>, <A HREF="default.php?cp=26">Sun Microsystems, Inc.</A>, <A HREF="default.php?cp=28">Tandy Corporation</A>, <A HREF="default.php?cp=5">Tektronix</A>, <A HREF="default.php?cp=70">Teletype Corporation</A>, <A HREF="default.php?cp=6">TeleVideo</A>, <A HREF="default.php?cp=2">Texas Instruments</A>, <A HREF="default.php?cp=41">Vector Graphics, Inc.</A>, <A HREF="default.php?cp=9">Visual Technology Inc.</A>, <A HREF="default.php?cp=99">VLSI Technology, Inc.</A>, <A HREF="default.php?cp=55">Volker-Craig Ltd.</A>, <A HREF="default.php?cp=23">Western Digital Corporation</A>, <A HREF="default.php?cp=13">Wyse Technology</A>, <A HREF="default.php?cp=102">Xebec Systems, Inc.</A>, <A HREF="default.php?cp=48">Xerox Corporation</A>, <A HREF="default.php?cp=40">Zilog, Inc.</A>';
	}
	function renderDocumentSummary()
	{
		return '18297 manuals, 6127 of which are online, at 52 websites';
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
