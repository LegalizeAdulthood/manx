<?php

require_once 'vendor/autoload.php';

require_once 'AdminPageBase.php';
require_once 'Config.php';
require_once 'File.php';
require_once 'UnknownPathDefs.php';
require_once 'WhatsNewPageFactory.php';

use Pimple\Container;

class WhatsNewPageBase extends AdminPageBase
{
    private $_factory;
    private $_timeStampProperty;
    private $_indexByDateUrl;
    private $_indexByDateFile;
    private $_baseUrl;
    private $_siteName;
    private $_menuType;
    private $_page;
    private $_title;

    public function __construct(Container $config)
    {
        parent::__construct($config);
        $this->_siteName = $config['siteName'];
        $config['timeStampProperty'] = $this->_siteName . '_whats_new_timestamp';
        $this->_indexByDateUrl = $config['indexByDateUrl'];
        $this->_indexByDateFile = $config['indexByDateFile'];
        $this->_baseUrl = $config['baseUrl'];
        $this->_menuType = $config['menuType'];
        $this->_page = $config['page'];
        $this->_title = $config['title'];
        $this->_fileSystem = $config['fileSystem'];
        $this->_factory = $config['whatsNewPageFactory'];
    }

    protected function getMenuType()
    {
        return $this->_menuType;
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
                $this->_manxDb->ignoreSitePath($this->_siteName, $path);
            }
        }
    }

    protected function renderBodyContent()
    {
        $total = $this->_manxDb->getSiteUnknownPathCount($this->_siteName);
        $title = $this->_title;
        if ($total == 0)
        {
            print <<<EOH
<h1>No New $title Publications Found</h1>

EOH;
            return;
        }

        print <<<EOH
<h1>New $title Publications</h1>


EOH;
        $start = array_key_exists('start', $this->_vars) ? $this->_vars['start'] : 0;
        $sortOrder = array_key_exists('sort', $this->_vars) ? $this->_vars['sort'] : SORT_ORDER_BY_ID;
        $sortById = ($sortOrder == SORT_ORDER_BY_ID) || ($sortOrder == SORT_ORDER_BY_ID_DESCENDING);
        $unknownPaths = $sortById ?
            $this->_manxDb->getSiteUnknownPathsOrderedById($this->_siteName, $start, $sortOrder == SORT_ORDER_BY_ID)
            : $this->_manxDb->getSiteUnknownPathsOrderedByPath($this->_siteName, $start, $sortOrder == SORT_ORDER_BY_PATH);
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
        $idHeader = sprintf('<a href="' . $this->_page . '?%1$s%2$s">Id</a>', $startParam, 'sort=' . $idSortParam);
        $pathHeader = sprintf('<a href="' . $this->_page . '?%1$s%2$s">Path</a>', $startParam, 'sort=' . $pathSortParam);
        $page = $this->_page;

        print <<<EOH
<form action="$page" method="POST">
<input type="hidden" name="start" value="$start" />
<input type="hidden" name="sort" value="$sortOrder" />
<table>
<tr><th>$idHeader</th><th>$pathHeader</th></tr>

EOH;
        $num = min(10, count($unknownPaths));
        for ($i = 0; $i < $num; ++$i)
        {
            $path = $unknownPaths[$i]['path'];
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $urlPath = self::escapeSpecialChars(trim($path));
            $checked = self::ignoreExtension($this->_manxDb, $extension) ? 'checked' : '';
            printf('<tr><td>%1$d.</td><td><input type="checkbox" id="ignore%2$d" name="ignore%2$d" value="%3$s" %5$s/>' . "\n" .
                '<a href="url-wizard.php?url=' . $this->_baseUrl . '/%4$s">%3$s</a></td></tr>' . "\n",
                $unknownPaths[$i]['id'], $i, $path, $urlPath, $checked);
        }
        print <<<EOH
</table>
<input type="submit" value="Ignore" />
</form>

EOH;
    }

    public static function ignoreExtension(Manx\IManxDatabase $manxDb, $extension)
    {
        $format = $manxDb->getFormatForExtension($extension);
        $imageFormats = array('TIFF' => 1, 'PNG' => 1, 'JPEG' => 1, 'GIF' => 1);
        return strlen($format) == 0 || array_key_exists($format, $imageFormats);
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
            print sprintf('<a class="navpage" href="' . $this->_page . '?start=%1$d%2$s"><b>&lt;&lt;</b></a>&nbsp;&nbsp;',
                $start - 10000, $sortParam);
        }
        if ($start - 1000 >= 0)
        {
            print sprintf('<a class="navpage" href="' . $this->_page . '?start=%1$d%2$s"><b>&lt;</b></a>&nbsp;&nbsp;',
                $start - 1000, $sortParam);
        }
        if ($start != 0)
        {
            printf('<a href="' . $this->_page . '?start=%1$d%2$s"><b>Previous</b></a>&nbsp;&nbsp;',
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
                print sprintf('<a class="navpage" href="' . $this->_page . '?start=%1$d%3$s">%2$d</a>&nbsp;&nbsp;',
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
            printf('<a href="' . $this->_page . '?start=%1$d%2$s"><b>Next</b></a>', $start + $rowsPerPage, $sortParam);
        }
        if ($start + 1000 < $total)
        {
            print sprintf('&nbsp;&nbsp;<a class="navpage" href="' . $this->_page . '?start=%1$d%2$s"><b>&gt;</b></a>',
                $start + 1000, $sortParam);
        }
        if ($start + 10000 < $total)
        {
            print sprintf('&nbsp;&nbsp;<a class="navpage" href="' . $this->_page . '?start=%1$d%2$s"><b>&gt;&gt;</b></a>',
                $start + 10000, $sortParam);
        }
        print "</div>\n";
    }

    public static function escapeSpecialChars($path)
    {
        return str_replace(" ", "%20", str_replace("#", urlencode("#"), $path));
    }
}
