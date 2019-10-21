<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';
require_once 'pages/AdminPageBase.php';

use Pimple\Container;

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
        foreach ($this->_manxDb->getMirrors() as $mirror)
        {
            printf("<tr><td align=\"center\">%d</td><td align=\"center\">%d</td><td>%s</td><td>%s</td><td align=\"center\">%d</td></tr>\n",
                $mirror['mirror_id'], $mirror['site'], $mirror['original_stem'], $mirror['copy_stem'], $mirror['rank']);
        }
        print "</table>\n";
    }

    protected function postPage()
    {
        throw new Exception("postPage unimplemented");
    }
}

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new MirrorPage($config);
$page->renderPage();
