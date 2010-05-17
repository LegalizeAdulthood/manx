<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html lang="en"><head><title>Manx</title>
<link rel="stylesheet" href="manx.css" type="text/css" />
</head>
<body id="VT100-NET">
<?php
	require 'ProductionManx.php';
	$manx = new ProductionManx();
?>
<div id="AUTH">Guest | <a href="login.php">Login</a></div>
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
<div id="MENU"><a class="first" href="default.php">Search</a><span class="nodisp">
| </span><a href="about.php">About</a><span class="nodisp">
| </span><a href="help.php">Help</a></div>

<div class="det"><form id="LOGINFORM" method="post" action="login.php">
<table id="LOGINBOX">
<tbody>
<tr><td><label for="USERFIELD">Username:</label></td>
<td><input type="text" id="USERFIELD" name="user" size="20" value="" /></td></tr>
<tr><td><label for="PASSFIELD">Password:</label></td>
<td><input type="password" id="PASSFIELD" name="pass" size="20" /></td></tr>
<tr><td colspan="2"><input type="submit" id="LOGIBUTT" name="LOGI" value="Login" /><input type="hidden" name="redirect" value="help.php?q=;cp=1" /></td></tr>
</tbody></table></form>

</div></body></html>
