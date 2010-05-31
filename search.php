<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html lang="en">
<head>
<title>Manx</title>
<link rel="stylesheet" type="text/css" href="manx.css" />
<link rel="shortcut icon" type="image/x-icon" href="manx.ico" />
</head>
<body id="VT100-NET">
<?php
	require_once 'Manx.php';
	$manx = Manx::getInstance();
?>
<div id="HEADER">
<div id="AUTH">Guest | <?php $manx->renderLoginLink(); ?></div>
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
<div id="MENU"><a class="firston">Search</a><span class="nodisp">
| </span><a href="about.php">About</a><span class="nodisp">
| </span><a href="help.php">Help</a></div>
</div>
<?php
	$manx->renderSearchResults();
?>
</body></html>
