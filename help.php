<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html lang="en">
<head>
<title>Manx</title>
<link rel="stylesheet" type="text/css" href="manx.css" />
<link href="manx.ico" type="image/x-icon" rel="shortcut icon" />
</head>
<body id="VT100-NET">
<?php
	require_once 'Manx.php';
	$manx = Manx::getInstance();
?>
<div id="HEADER">
<div id="AUTH">Guest | <?php $manx->renderLoginLink('help.php'); ?></div>
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
<div id="MENU"><a class="first" href="search.php">Search</a><span class="nodisp">
| </span><a href="about.php">About</a><span class="nodisp">
| </span><a class="on">Help</a></div>
</div>
<div class="det"><h1>Help</h1>
<p>To search, enter a word or words from the title of the manual you're
looking for, or some letters or digits from the part number. Each word
you search for must contain at least three letters (or you'd get loads
of false hits from part numbers). When you're searching for part numbers,
don't worry about the difference between '0' and 'O'; DEC frequently
confused the two in part numbers, so they're treated the same when
searching.</p>
<p>The search results consist of pages showing ten manuals at a time.
The results show the manual's part number, date of publication, title
and status, which is either blank or some combination of "Online" and
"Superseded". Superseded means that a manual has either been
<em>revised</em> since this edition or it has been <em>replaced</em>
completely. Superseded manuals might still be online, so this catalogue
still links to them. The manual that superseded this one will normally
be found in the same search results.</p>
<p>The title of each manual will be a link if there is more information
available. If the status is "Online", clicking the link will show you
all the copies available. If the title is a link but the manual isn't
shown as being online then at least the Table of Contents is available.
This isn't too useful (or common) but if it appears that the manual
contains information you would like, you can at least ask on appropriate
newsgroups or mailing lists whether anyone has a copy.</p>
</div></body></html>
