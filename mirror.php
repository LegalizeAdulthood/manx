<?php

require_once 'Manx.php';
require_once 'AdminPageBase.php';

class MirrorPage extends AdminPageBase
{
	protected function getMenuType()
	{
		return MenuType::Mirror;
	}

	protected function renderBodyContent()
	{
		print "<h1>Mirrors</h1>\n";
		print "<table border=\"1\">\n";
		print "<tr><th>Id</th><th>Site</th><th>Original Stem</th><th>Copy Stem</th><th>Rank</th></tr>\n";
		foreach ($this->_manx->getMirrors() as $mirror)
		{
			printf("<tr><td align=\"center\">%d</td><td align=\"center\">%d</td><td>%s</td><td>%s</td><td align=\"center\">%d</td></tr>\n",
				$mirror['mirror_id'], $mirror['site'], $mirror['original_stem'], $mirror['copy_stem'], $mirror['rank']);
		}
		print "</table>\n";
	}
}

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new MirrorPage($manx, $vars);
$page->renderPage();

?>
