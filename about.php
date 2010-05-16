<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
<title>Manx</title>
<link rel="stylesheet" type="text/css" href="manx.css">
<link href="manx.ico" type="image/x-icon" rel="shortcut icon">
<body id="VT100-NET">
<?php
	require 'ProductionManx.php';
	$manx = new ProductionManx();
?>
<div id="AUTH" />Guest | <?php print $manx->renderLoginLink('about.php'); ?></div>
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
<div id="MENU"><a class="first" href="default.php">Search</a><span class="nodisp">
| </span><a class="on">About</a><span class="nodisp">
| </span><a href="help.php">Help</a></div>
<DIV CLASS="det"><h1>About Manx</h1>
<p>Manx is a catalogue of manuals for old computers.</p>
<p>Many of these manuals can't be found by search engines because the
manuals have been scanned but haven't been converted to text. Manx's
search engine is currently limited to searching part numbers, titles
and keywords of these manuals, though there are plans to search Tables
of Contents and full text when these become available.</p>
<P>This catalogue mostly covers manufacturers of minicomputers and
mainframes, or associated devices such as terminals and printers.
Tiziano's <A HREF="http://1000bit.net/">1000 BiT</A> is the best
catalogue for microcomputers.</P>
<P><STRONG>Manx</STRONG> currently knows about <?php print $manx->renderDocumentSummary(); ?>.</P>
<P>Manx covers the following companies:
<?php
	$manx->renderCompanyList();
?>
</P>
<p>The list below shows the websites included in Manx. As Manx is
still being built, you will find that not all of the holdings of
these websites have been catalogued. If you know of manuals on other
sites, please let me know about them. Even if a site only contains
one manual, it is worth including. This list is ordered by date of
inclusion in Manx.</p>
<?php
	$manx->renderSiteList();
?>
<p>Some of these sites are marked as being Low Bandwidth. They are either on
a home DSL line, or the owner has indicated that they should not be mirrored
with tools. It isn't a comment on the usefulness of the site!</p>
</div></body></html>
