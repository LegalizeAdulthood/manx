<?php

require_once 'IManx.php';

class MenuType
{
	const Undefined = 0;
	const Search = 1;
	const About = 2;
	const Help = 3;
	const Publication = 4;
	const Mirror = 5;
	const Company = 6;
	const Copy = 7;
	const Site = 8;
	const UrlWizard = 9;
}

class PageBase
{
	protected $_manx;
	protected $_manxDb;
	protected $_topDir;
	protected $_user;

	public function __construct(IManx $manx)
    {
		$this->_manx = $manx;
		$this->_manxDb = $manx->getDatabase();
		$this->_topDir = str_repeat('../', count(explode('/', $_SERVER['PATH_INFO'])) - 1);
		$this->_user = $this->_manx->getUserFromSession();
	}

	public function __destruct()
	{
		$this->_manx = null;
	}

	public function renderPage()
	{
		$this->renderHeader();
		$this->renderBody();
	}

	protected function getTitle()
	{
		return "Manx";
	}

	protected function getMenuType()
	{
		return MenuType::Undefined;
	}

	protected function renderLink($rel, $type, $href)
	{
		printf('<link rel="%s" type="%s" href="%s" />' . "\n", $rel, $type, $this->_topDir . $href);
	}

	protected function renderHeader()
	{
		header("Content-Type: text/html; charset=utf-8");
		print <<<EOH
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html lang="en">
<head>
EOH;
		printf("<title>%s</title>\n", $this->getTitle());
		$this->renderLink("stylesheet", "text/css", "manx.css");
		$this->renderLink("shortcut icon", "image/x-icon", "manx.ico");
		$this->renderLink("alternate", "application/rss+xml", "rss.php");
		$this->renderHeaderContent();
		print "</head>\n";
	}

	protected function renderHeaderContent()
	{
	}

	protected function renderBody()
	{
		$this->renderBodyHeader();
		$this->renderBodyContent();
		$this->renderBodyFooter();
	}

	private function renderMenuItem($first, $textOnly, $href, $text)
	{
		if ($textOnly)
		{
			printf('<a class="%son">%s</a>', $first ? 'first' : '', $text);
		}
		else
		{
			printf('<a %shref="%s">%s</a>', $first ? 'class="first" ' : '', $this->_topDir . $href, $text);
		}
	}

	private function renderMenuSeparator()
	{
		print '<span class="nodisp"> | </span>';
	}

	private function renderAdminMenu($menu)
	{
		if ($this->_user->isAdmin())
		{
			//$this->renderMenuSeparator();
			//$this->renderMenuItem(false, ($menu == MenuType::Company), "company.php", "Company");
			$this->renderMenuSeparator();
			$this->renderMenuItem(false, ($menu == MenuType::UrlWizard), "url-wizard.php", "URL Wizard");
			//$this->renderMenuItem(false, ($menu == MenuType::Publication), "publication.php", "Publication");
			//$this->renderMenuSeparator();
			//$this->renderMenuItem(false, ($menu == MenuType::Copy), "copy.php", "Copy");
			//$this->renderMenuSeparator();
			$this->renderMenuSeparator();
			$this->renderMenuItem(false, ($menu == MenuType::Site), "site.php", "Site");
			$this->renderMenuSeparator();
			$this->renderMenuItem(false, ($menu == MenuType::Mirror), "mirror.php", "Mirror");
		}
	}

	private function renderMenu()
	{
		$menu = $this->getMenuType();

		print '<div id="MENU">';
		$this->renderMenuItem(true, ($menu == MenuType::Search), "search.php", "Search");
		$this->renderMenuSeparator();
		$this->renderMenuItem(false, ($menu == MenuType::About), "about.php", "About");
		$this->renderMenuSeparator();
		$this->renderMenuItem(false, ($menu == MenuType::Help), "help.php", "Help");
		$this->renderAdminMenu($menu);
		print "</div>\n";
	}

	protected function renderBodyHeader()
	{
		print <<<EOH
<body id="VT100-NET">
<div id="HEADER">
EOH;
		$this->renderAuthorization();
		print <<<EOH
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
EOH;
		$this->renderMenu();
		print <<<EOH
</div>
<div class="det">
EOH;
	}

	protected function renderBodyFooter()
	{
		print <<<EOH
</div></body></html>
EOH;
	}

	protected function redirect($target)
	{
		header("Status: 303 See Also");
		header("Location: " . $target);
		header("Content-Type: text/plain");
		print "Redirecting to " . $target;
	}

	public static function getRelativePrefixFromPathInfo()
	{
		return str_repeat('../', count(split('/', $_SERVER['PATH_INFO'])) - 1);
	}

	public function renderLoginLink($server)
	{
		$redirect = $server['PHP_SELF'];
		if (array_key_exists('QUERY_STRING', $server) and strlen($server['QUERY_STRING']) > 0)
		{
			$redirect = sprintf("%s?%s", $redirect, $server['QUERY_STRING']);
		}
		$prefix = PageBase::getRelativePrefixFromPathInfo();
		printf('<a href="%slogin.php?redirect=%s">Login</a>', $prefix, urlencode($redirect));
	}

	private function renderLogoutLink()
	{
		$prefix = PageBase::getRelativePrefixFromPathInfo();
		printf('<a href="%slogin.php?LOGO=1&redirect=%ssearch.php">Logout</a>', $prefix, $prefix);
	}

	protected function renderAuthorization()
	{
		$user = User::getInstanceFromSession($this->_manxDb);
		print '<div id="AUTH">' . $user->displayName() . ' | ';
		if ($user->isLoggedIn())
		{
			$this->renderLogoutLink();
		}
		else
		{
			$this->renderLoginLink($_SERVER);
		}
		print "</div>\n";
	}
}

?>
