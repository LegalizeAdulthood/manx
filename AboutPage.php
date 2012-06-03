<?php

require_once 'PageBase.php';

class AboutPage extends PageBase
{
	public function __construct($manx)
	{
		parent::__construct($manx);
	}

	protected function getMenuType()
	{
		return MenuType::About;
	}

	protected function renderBodyContent()
	{
		print <<<EOH
<h1>About Manx</h1>
<p>Manx is a catalogue of manuals for old computers.</p>
<p>Many of these manuals can't be found by search engines because the
manuals have been scanned but haven't been converted to text. Manx's
search engine is currently limited to searching part numbers, titles
and keywords of these manuals, though there are plans to search Tables
of Contents and full text when these become available.</p>
<p>This catalogue mostly covers manufacturers of minicomputers and
mainframes, or associated devices such as terminals and printers.
Tiziano's <a href="http://1000bit.net/">1000 BiT</a> is the best
catalogue for microcomputers.</p>
EOH;

		print "<p><strong>Manx</strong> currently knows about ";
		$this->_manx->renderDocumentSummary();
		print ".</p>\n";

		print "<p>Manx covers the following companies:\n";
		$this->_manx->renderCompanyList();
		print <<<EOH
</p>
<p>The list below shows the websites included in Manx. As Manx is
still being built, you will find that not all of the holdings of
these websites have been catalogued. If you know of manuals on other
sites, please let me know about them. Even if a site only contains
one manual, it is worth including. This list is ordered by date of
inclusion in Manx.</p>
EOH;

		$this->_manx->renderSiteList();

		print <<<EOH
<p>Some of these sites are marked as being Low Bandwidth. They are either on
a home DSL line, or the owner has indicated that they should not be mirrored
with tools. It isn't a comment on the usefulness of the site!</p>
EOH;
	}
}

?>
