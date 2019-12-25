<?php

namespace Manx;

require_once 'vendor/autoload.php';

use Pimple\Container;

class SitePage extends AdminPageBase
{
    protected function getMenuType()
    {
        return MenuType::Site;
    }

    protected function renderBodyContent()
    {
        print "<h1>Sites</h1>\n";
        print "<table border=\"1\">\n";
        print "<tr>";
        print "<th>Id</th>";
        print "<th>Name</th>";
        print "<th>URL</th>";
        print "<th>Description</th>";
        print "<th>Copy Base</th>";
        print "<th>Low</th>";
        print "<th>Live</th>";
        print "<th>Display Order</th>";
        print "</tr>\n";
        foreach ($this->_manxDb->getSites() as $site)
        {
            printf("<tr>"
                    . "<td align=\"center\">%d</td>"
                    . "<td>%s</td>"
                    . "<td>%s</td>"
                    . "<td>%s</td>"
                    . "<td>%s</td>"
                    . "<td>%s</td>"
                    . "<td>%s</td>"
                    . "<td align=\"center\">%d</td>"
                    . "</tr>\n",
                $site['site_id'], $site['name'], $site['url'], $site['description'],
                $site['copy_base'], $site['low'], $site['live'], $site['display_order']);
        }
        print "</table>\n";
    }

    protected function postPage()
    {
        throw new Exception("postPage not implemented");
    }
}
