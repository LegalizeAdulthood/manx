<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html lang="en">
<head>
<title>Manx: snake</title>
<link rel="stylesheet" type="text/css" href="manx.css" />
<link rel="shortcut icon" type="image/x-icon" href="manx.ico" />
</head>
<body id="VT100-NET">
<?php
	require 'ProductionManx.php';
	$manx = new ProductionManx();
?>
<div id="AUTH">Guest | <?php print $manx->renderLoginLink(); ?></div>
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
<div id="MENU"><a class="firston">Search</a><span class="nodisp">
| </span><a href="about.php">About</a><span class="nodisp">
| </span><a href="help.php">Help</a></div>
<div id="SEARCHFORM"><form action="default.php" method="get" name="f"><div class="field">Company:
<?php
	$manx->renderDefaultCompanies();
?>
Keywords: <input id="Q" name="q" value="" size="20" maxlength="256" />
Online only: <input type="checkbox" name="on" />
<input id="GO" type="submit" value="Search" /></div></form></div>
<?php
print $manx->renderDefaultSearchResults();
?>
</body></html>
