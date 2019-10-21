<?php

require_once 'PageBase.php';

class AboutPage extends PageBase
{
    protected function getMenuType()
    {
        return MenuType::About;
    }

    protected function renderBodyContent()
    {
        print <<<EOH
<h1>About Manx</h1>

<p>Manx is a catalog of manuals for old computers.
Manx is an <a href="http://manx.codeplex.com">open source project</a> hosted
on <a href="http://www.codeplex.com">CodePlex</a>.</p>

<p>Many of these manuals can't be found by search engines because the
manuals have been scanned but haven't been converted to text.  Google
can index deep into these scanned documents because they will OCR scanned
images in PDFs and index the resulting text.  However, manx contains
additional metadata on the online documents as well as information about
documents known to exist but not available online.  Manx's
search engine is currently limited to searching part numbers, titles
and keywords of these manuals.</p>

<p>This catalog mostly covers manufacturers of minicomputers and
mainframes, or associated devices such as terminals and printers.
Tiziano's <a href="http://www.1000bit.it/">1000 BiT</a> is the best
catalogue for microcomputers.</p>

EOH;
        print "<p><strong>Manx</strong> currently knows about ";
        $this->renderDocumentSummary();
        print ".</p>\n";
        print "<p>Manx covers the following companies:\n";
        $this->renderCompanyList();
        print <<<EOH
</p>

<p>The list below shows the websites included in Manx. As Manx is
still being built, you will find that not all of the holdings of
these websites have been catalogued. If you know of manuals on other
sites, please <a href="http://manx.codeplex.com/WorkItem/Create">create
a work item</a> for the Data component. Even if a site only contains
one manual, it is worth including. This list is ordered by date of
inclusion in Manx.</p>
EOH;
        $this->renderSiteList();
        print <<<EOH
<p>Some of these sites are marked as being Low Bandwidth. They are either on
a home DSL line, or the owner has indicated that they should not be mirrored
with tools. It isn't a comment on the usefulness of the site!</p>

<h1>Credits</h1>

<p>Paul Williams wrote the original implementation of Manx in perl and
entered all the initial data.  Thank you, Paul!</p>

<p><a href="http://www.jetbrains.com">JetBrains</a> donated a license of the
<a href="http://www.jetbrains.com/phpstorm/">PHPStorm PHP development environment</a>
for use in developing this code.  Thank you, JetBrains!</p>

<p>Richard Thomson rewrote manx in PHP when Paul Williams took his
implementation offline.  Richard is the current maintainer of the code.</p>
EOH;
    }

    public function renderDocumentSummary()
    {
        echo $this->_manxDb->getDocumentCount(), ' manuals, ',
            $this->_manxDb->getOnlineDocumentCount(), ' of which are online, at ',
            $this->_manxDb->getSiteCount(), ' websites';
    }

    public function renderCompanyList()
    {
        try
        {
            $rows = $this->_manxDb->getCompanyList();
            $count = count($rows);
            $i = 0;
            foreach ($rows as $row)
            {
                print '<a href="search.php?cp=' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</a>';
                $i++;
                if ($i < $count)
                {
                    print ', ';
                }
            }
        }
        catch (Exception $e)
        {
            print "Unexpected error: " . $e->getMessage();
        }
    }

    public function renderSiteList()
    {
        try
        {
            print '<ul>';
            foreach ($this->_manxDb->getSiteList() as $row)
            {
                print '<li><a href="' . $row['url'] . '">' . htmlspecialchars($row['description']) . '</a>';
                if ('Y' == $row['low'])
                {
                    print ' <span class="warning">(Low Bandwidth)</span>';
                }
                print '</li>';
            }
            print '</ul>';
        }
        catch (Exception $e)
        {
            print "Unexpected error: " . $e->getMessage();
        }
    }
}
