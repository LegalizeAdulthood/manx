<?php
require_once 'Manx.php';

function GenerateSessionId()
{
	return sprintf("%s.%06d\n", strftime("%Y%m%d%H%M%S", gmmktime()), rand(0, 1000000));
}

function printHeader($title)
{
	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">';
	print '<html lang="en"><head><title>' . $title . '</title>';
	print '<link rel="stylesheet" href="manx.css" type="text/css" />';
	print '</head>';
}

function dumpKey($name, $map)
{
	dumpText($name, array_key_exists($name, $map) ? $map[$name] : "(undefined)");
}

function dumpText($name, $text)
{
	print "<dt>" . $name . "</dt><dd>"
		. $text
		. "</dd>\n";
}

function dumpVar($name)
{
	dumpKey($name, $_POST);
}


function printBody($loginFailed, $cookieFailed)
{
	print '<body id="VT100-NET">';
	print '<div id="HEADER">';
	print '<div id="AUTH">Guest | <a href="login.php">Login</a></div>';
	print '<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>';
	print '<div id="MENU"><a class="first" href="search.php">Search</a><span class="nodisp">';
	print '| </span><a href="about.php">About</a><span class="nodisp">';
	print '| </span><a href="help.php">Help</a></div>';
	print '</div>';
	print '<div class="det">';
	print '<form id="LOGINFORM" method="post" action="login.php">';
	print '<table id="LOGINBOX">';
	print '<tbody>';
	print '<tr><td><label for="USERFIELD">Username:</label></td>';
	print '<td><input type="text" id="USERFIELD" name="user" size="20" value="" /></td></tr>';
	print '<tr><td><label for="PASSFIELD">Password:</label></td>';
	print '<td><input type="password" id="PASSFIELD" name="pass" size="20" /></td></tr>';
	print '<tr><td colspan="2">';
	print '<input type="submit" id="LOGIBUTT" name="LOGI" value="Login" />';
	print '<input type="hidden" name="redirect" value="search.php?q=;cp=1" /></td></tr>';
	print '</tbody></table></form></div>';
	print "<h3>Error Messages</h3>\n";
	print "<dl>\n";
	dumpVar('LOGO');
	dumpVar('LOGI');
	dumpVar('user');
	dumpVar('pass');
	dumpVar('redirect');
	dumpKey('request_url', $_SERVER);
	print "</dl>\n";
	if ($loginFailed)
	{
		print '<p style="color:red">Username or password incorrect</p>';
	}
	if ($cookieFailed)
	{
		print '<p style="color:red">You need to enable cookies to login</p>';
	}
	print '</body></html>';
}

function redirect($target)
{
	header("Status: 303 See Also");
	header("Location: " . $target);
	header("Content-Type: text/plain; charset=utf-8");
	print "Redirecting to " . $target;
}

function login()
{
	$manx = Manx::getInstance();

	$loginFailed = false;
	if (array_key_exists('LOGO', $_POST))
	{
		ResetManxCookie();
		if (array_key_exists('redirect', $_POST))
		{
			redirect($_POST['redirect']);
			exit;
		}
	}
	else
	{
		if (array_key_exists('LOGI', $_POST))
		{
			if ($manx->loginUser($_POST['user'], sha1($_POST['pass'])))
			{
				// Now take our cookie and redirect back to this script to test
				redirect($_SERVER['request_url'] . '?check=1&redirect=' . $_POST['redirect']);
				exit;
			}
			else
			{
				$loginFailed = true;
			}
		}
	}

	$cookieFailed = false;
	if (array_key_exists('check', $_GET))
	{
		if (array_key_exists('manxSession', $_COOKIE) && $_COOKIE['manxSession'] != 'OUT')
		{
			$redirect = array_key_exists('redirect', $_POST) ? $_POST['redirect'] : 'http://manx.classiccmp.org/test/search.php';
			redirect($redirect);
			exit;
		}
		else
		{
			$cookieFailed = true;
		}
	}

	printHeader('Manx');
	printBody($loginFailed, $cookieFailed);
}

login();
?>
