<?php

require_once 'Manx.php';

class MenuType
{
	const Undefined = 0;
	const Search = 1;
	const About = 2;
	const Help = 3;
}

class PageBase
{
	protected $_manx;

	public function __construct()
    {
		$this->_manx = Manx::getInstance();
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

	private function renderHeader()
	{
		header("Content-Type: text/html; charset=utf-8");
		print <<<EOH
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html lang="en">
<head>
EOH;
		print "<title>" . $this->getTitle() . "</title>\n";
		print <<<EOH
<link rel="stylesheet" type="text/css" href="manx.css" />
<link href="manx.ico" type="image/x-icon" rel="shortcut icon" />
</head>
EOH;
	}

	private function renderBody()
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
			printf('<a %shref="%s">%s</a>', $first ? 'class="first" ' : '', $href, $text);
		}
	}

	private function renderMenu()
	{
		$menu = $this->getMenuType();

		print '<div id="MENU">';
		$this->renderMenuItem(true, ($menu == MenuType::Search), "search.php", "Search");
		print '<span class="nodisp"> | </span>';
		$this->renderMenuItem(false, ($menu == MenuType::About), "about.php", "About");
		print '<span class="nodisp"> | </span>';
		$this->renderMenuItem(false, ($menu == MenuType::Help), "help.php", "Help");
		print "</div>\n";
	}

	private function renderBodyHeader()
	{
		print <<<EOH
<body id="VT100-NET">
<div id="HEADER">
EOH;
		$this->_manx->renderAuthorization();
		print <<<EOH
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
EOH;
		$this->renderMenu();
		print <<<EOH
</div>
<div class="det">
EOH;
	}

	public function renderBodyFooter()
	{
		print <<<EOH
</div></body></html>
EOH;
	}
}

?>
