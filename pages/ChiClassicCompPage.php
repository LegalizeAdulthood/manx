<?php

require_once 'AdminPageBase.php';
require_once 'BitSaversPageFactory.php';
require_once 'Config.php';
require_once 'UnknownPathDefs.php';

define('CCC_INDEX_BY_DATE_FILE', 'chiClassicComp-IndexByDate.txt');
define('CCC_INDEX_BY_DATE_URL', 'http://chiclassiccomp.org/docs/content/IndexByDate.txt');
define('CCC_TIMESTAMP_PROPERTY', 'chiclassiccomp_whats_new_timestamp');

class ChiClassicCompPage extends AdminPageBase
{
    private $_factory;

    public function __construct($manx, $vars, IBitSaversPageFactory $factory = null)
    {
        parent::__construct($manx, $vars);
        if ($factory === null)
        {
            $factory = new BitSaversPageFactory();
        }
        $this->_factory = $factory;
        if ($this->needIndexByDateFile())
        {
            $this->getIndexByDateFile();
            $this->parseIndexByDateFile();
        }
    }

    private function needIndexByDateFile()
    {
        $timeStamp = $this->_manxDb->getProperty(CCC_TIMESTAMP_PROPERTY);
        if ($timeStamp === false)
        {
            return true;
        }
        $urlInfo = $this->_factory->createUrlInfo(CCC_INDEX_BY_DATE_URL);
        $lastModified = $urlInfo->lastModified();
        if ($lastModified === false)
        {
            $lastModified = $this->_factory->getCurrentTime();
        }
        $this->_manxDb->setProperty(CCC_TIMESTAMP_PROPERTY, $lastModified);
        return $lastModified > $timeStamp;
    }

    private function getIndexByDateFile()
    {
        $transfer = $this->_factory->createUrlTransfer(CCC_INDEX_BY_DATE_URL);
        $transfer->get(PRIVATE_DIR . CCC_INDEX_BY_DATE_FILE);
        $this->_manxDb->setProperty(CCC_TIMESTAMP_PROPERTY, $this->_factory->getCurrentTime());
    }

    private function parseIndexByDateFile()
    {
        $indexByDate = $this->_factory->openFile(PRIVATE_DIR . CCC_INDEX_BY_DATE_FILE, 'r');
        while (!$indexByDate->eof())
        {
            $line = substr(trim($indexByDate->getString()), 20);
            if (strlen($line) && $this->pathUnknown($line))
            {
                $this->addUnknownPath($line);
            }
        }
    }

    private function pathUnknown($line)
    {
        $url = 'http://chiclassiccomp.org/docs/content/' . self::escapeSpecialChars($line);
        return $this->_manxDb->copyExistsForUrl($url) === false
            && $this->_manxDb->bitSaversIgnoredPath($line) === false;
    }

    private function addUnknownPath($line)
    {
        $this->_manxDb->addChiClassicCompUnknownPath($line);
    }

    protected function getMenuType()
    {
        return MenuType::ChiClassicComp;
    }

    protected function postPage()
    {
        $this->ignorePaths();
        PageBase::renderPage();
    }

    protected function ignorePaths()
    {
        $ignored = array();
        for ($i = 0; $i < 10; ++$i)
        {
            $key = sprintf('ignore%1$d', $i);
            if (array_key_exists($key, $this->_vars))
            {
                array_push($ignored, $this->_vars[$key]);
            }
        }
        if (count($ignored))
        {
            foreach ($ignored as $path)
            {
                $this->_manxDb->ignoreChiClassicCompPath($path);
            }
        }
    }

    protected function renderBodyContent()
    {
        $total = $this->_manxDb->getChiClassicCompUnknownPathCount();
        if ($total == 0)
        {
            print <<<EOH
<h1>No New ChiClassicComp Publications Found</h1>

EOH;
            return;
        }

        print <<<EOH
<h1>New ChiClassicComp Publications</h1>


EOH;
        $start = array_key_exists('start', $this->_vars) ? $this->_vars['start'] : 0;
        $sortOrder = array_key_exists('sort', $this->_vars) ? $this->_vars['sort'] : CCC_SORT_ORDER_BY_ID;
        $sortById = ($sortOrder == SORT_ORDER_BY_ID) || ($sortOrder == SORT_ORDER_BY_ID_DESCENDING);
        $unknownPaths = $sortById ?
            $this->_manxDb->getChiClassicCompUnknownPathsOrderedById($start, $sortOrder == SORT_ORDER_BY_ID)
            : $this->_manxDb->getChiClassicCompUnknownPathsOrderedByPath($start, $sortOrder == SORT_ORDER_BY_PATH);
        $this->renderPageSelectionBar($start, $total);
        $startParam = $start > 0 ? sprintf('start=%1$d&', $start) : '';
        if ($sortById)
        {
            $idSortParam = ($sortOrder == SORT_ORDER_BY_ID) ? SORT_ORDER_BY_ID_DESCENDING : SORT_ORDER_BY_ID;
            $pathSortParam = SORT_ORDER_BY_PATH;
        }
        else
        {
            $idSortParam = SORT_ORDER_BY_ID;
            $pathSortParam = ($sortOrder == SORT_ORDER_BY_PATH) ? SORT_ORDER_BY_PATH_DESCENDING : SORT_ORDER_BY_PATH;
        }
        $idHeader = sprintf('<a href="chiclassiccomp.php?%1$s%2$s">Id</a>', $startParam, 'sort=' . $idSortParam);
        $pathHeader = sprintf('<a href="chiclassiccomp.php?%1$s%2$s">Path</a>', $startParam, 'sort=' . $pathSortParam);

        print <<<EOH
<form action="chiclassiccomp.php" method="POST">
<input type="hidden" name="start" value="$start" />
<input type="hidden" name="sort" value="$sortOrder" />
<table>
<tr><th>$idHeader</th><th>$pathHeader</th></tr>

EOH;
        $num = min(10, count($unknownPaths));
        for ($i = 0; $i < $num; ++$i)
        {
            $path = $unknownPaths[$i]['path'];
            $urlPath = self::escapeSpecialChars(trim($path));
            $checked = preg_match('/.*\.(jpg|bin|tif|dat|zip|txt)$/i', $path) ? 'checked' : '';
            printf('<tr><td>%1$d.</td><td><input type="checkbox" id="ignore%2$d" name="ignore%2$d" value="%3$s" %5$s/>' . "\n" .
                '<a href="url-wizard.php?url=http://chiclassiccomp.org/docs/content/%4$s">%3$s</a></td></tr>' . "\n",
                $unknownPaths[$i]['id'], $i, $path, $urlPath, $checked);
        }
        print <<<EOH
</table>
<input type="submit" value="Ignore" />
</form>

EOH;

    }

    protected function renderPageSelectionBar($start, $total)
    {
        $sortOrder = array_key_exists('sort', $this->_vars) ?
            $this->_vars['sort'] : SORT_ORDER_BY_ID;
        $sortParam = ($sortOrder == SORT_ORDER_BY_ID) ? '' : '&sort=' . $sortOrder;
        print '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;';
        $rowsPerPage = 10;
        if ($start - 10000 >= 0)
        {
            print sprintf('<a class="navpage" href="chiclassiccomp.php?start=%1$d%2$s"><b>&lt;&lt;</b></a>&nbsp;&nbsp;',
                $start - 10000, $sortParam);
        }
        if ($start - 1000 >= 0)
        {
            print sprintf('<a class="navpage" href="chiclassiccomp.php?start=%1$d%2$s"><b>&lt;</b></a>&nbsp;&nbsp;',
                $start - 1000, $sortParam);
        }
        if ($start != 0)
        {
            printf('<a href="chiclassiccomp.php?start=%1$d%2$s"><b>Previous</b></a>&nbsp;&nbsp;',
                max(0, $start - $rowsPerPage), $sortParam);
        }

        $firstPage = intval($start / (10 * $rowsPerPage)) * 10 + 1;
        $lastPageNum = intval(($total + $rowsPerPage - 1) / $rowsPerPage);
        $lastPageStart = ($lastPageNum - 1) * $rowsPerPage;
        $currPageNum = $firstPage;
        $currPageStart = ($currPageNum - 1) * $rowsPerPage;

        $numIndices = 0;
        while ($numIndices++ < 10)
        {
            if ($start == $currPageStart)
            {
                print '<b class="currpage">' . $currPageNum . '</b>&nbsp;&nbsp;';
            }
            else
            {
                print sprintf('<a class="navpage" href="chiclassiccomp.php?start=%1$d%3$s">%2$d</a>&nbsp;&nbsp;',
                    $currPageStart, $currPageNum, $sortParam);
            }
            ++$currPageNum;
            $currPageStart += $rowsPerPage;
            if ($currPageStart > $lastPageStart)
            {
                break;
            }
        }
        if ($start != $lastPageStart)
        {
            printf('<a href="chiclassiccomp.php?start=%1$d%2$s"><b>Next</b></a>', $start + $rowsPerPage, $sortParam);
        }
        if ($start + 1000 < $total)
        {
            print sprintf('&nbsp;&nbsp;<a class="navpage" href="chiclassiccomp.php?start=%1$d%2$s"><b>&gt;</b></a>',
                $start + 1000, $sortParam);
        }
        if ($start + 10000 < $total)
        {
            print sprintf('&nbsp;&nbsp;<a class="navpage" href="chiclassiccomp.php?start=%1$d%2$s"><b>&gt;&gt;</b></a>',
                $start + 10000, $sortParam);
        }
        print "</div>\n";
    }

    public static function escapeSpecialChars($path)
    {
        return str_replace(" ", "%20", str_replace("#", urlencode("#"), str_replace("&", urlencode("&"), $path)));
    }
}
