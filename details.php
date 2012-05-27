<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html lang="en">
<head>
<title>PDP-11/70 Hardware Student Handouts &ndash; Manx</title>
<link rel="stylesheet" type="text/css" href="../manx.css" />
<link href="manx.ico" type="image/x-icon" rel="shortcut icon" />
</head>
<body id="VT100-NET">
<?php
	require_once 'Manx.php';
	$manx = Manx::getInstance();
?>
<div id="HEADER">
<?php $manx->renderAuthorization(); ?>
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
<div id="MENU"><a class="first" href="../search.php">Search</a><span class="nodisp">
| </span><a href="../about.php">About</a><span class="nodisp">
| </span><a href="../help.php">Help</a></div>
</div>
<?php
	$manx->renderDetails($_SERVER['PATH_INFO']);
?>
</body></html>

