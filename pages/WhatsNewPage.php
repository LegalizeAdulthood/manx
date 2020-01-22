<?php

namespace Manx;

require_once __DIR__ . '/../vendor/autoload.php';

// For SORT_ORDER_xxx
require_once __DIR__ . '/UnknownPathDefs.php';

use Pimple\Container;

class WhatsNewPage extends AdminPageBase
{
    /** @var IWhatsNewPageFactory */
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
        $vars = $config['vars'];
        $this->_parentDirId = array_key_exists('parentDir', $vars) ? $vars['parentDir'] : -1;
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
        $ignoredIds = [];
        for ($i = 0; $i < 10; ++$i)
        {
            $key = sprintf('ignore%1$d', $i);
            if (array_key_exists($key, $this->_vars))
            {
                $ignoredIds[] = $this->_vars[$key];
            }
        }
        if (count($ignoredIds))
        {
            $this->_manxDb->ignoreSitePaths($ignoredIds);
        }
    }

    protected function renderBodyContent()
    {
        if ($this->_parentDirId != -1)
        {
            $thisDir = $this->_manxDb->getSiteUnknownDir($this->_parentDirId);
        }
        else
        {
            $thisDir = ['id' => -1, 'path' => '', 'parent_dir_id' => -1, 'part_regex' => '', 'ignored' => 0];
        }
        $currentDir = $thisDir['path'];
        $dirs = [];
        foreach ($this->_manxDb->getSiteUnknownDirectories($this->_siteName, $this->_parentDirId) as $dir)
        {
            if ($dir['ignored'] == 0)
            {
                $dirs[] = $dir;
            }
        }
        $files = $this->_manxDb->getSiteUnknownPaths($this->_siteName, $this->_parentDirId);
        $title = $this->_title;
        if (count($dirs) + count($files) == 0)
        {
            if ($this->_parentDirId == -1)
            {
                print <<<EOH
<h1>No New $title Publications Found</h1>

EOH;
            }
            else
            {
                print <<<EOH
<h1>No New $title $currentDir Publications Found</h1>


EOH;
                printf("<ul>\n<li><a href=\"%s&parentDir=%d\">(parent)</a></li>\n</ul>\n", $this->_page, $thisDir['parent_dir_id']);
            }
            return;
        }

        print <<<EOH
<h1>New $title $currentDir Publications</h1>


EOH;

        if ($this->_parentDirId != -1)
        {
            array_unshift($dirs, ['id' => $thisDir['parent_dir_id'], 'path' => '(parent)', 'parent_dir_id' => -1, 'part_regex' => '']);
        }
        if (count($dirs) > 0)
        {
            printf("<ul>\n");
            foreach ($dirs as $dir)
            {
                printf('<li><a href="%s&parentDir=%d">%s</a></li>' . "\n", $this->_page, $dir['id'], $dir['path']);
            }
            printf("</ul>\n");
        }

        if (count($files) > 0)
        {
            $siteName = $this->_siteName;
            $parentDirId = $this->_parentDirId;
            $page = $this->_page;
            print <<<EOH
<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="$siteName" />
<input type="hidden" name="parentDir" value="$parentDirId" />
<table>
<tr><th>Ignored?</th><th>File</th></tr>

EOH;
            for ($i = 0; $i < count($files); ++$i)
            {
                $file = $files[$i];
                $path = $file['path'];
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $urlPath = self::escapeSpecialChars(trim($path));
                $checked = $file['ignored'] == 1 || self::ignoreExtension($this->_manxDb, $extension) ? ' checked' : '';
                printf('<tr><td><input type="checkbox" id="ignore%1$d" name="ignore%1$d" value="%2$s"%5$s/></td>' . "\n"
                    .  '<td><a href="url-wizard.php?id=%2$d&url=' . $this->_baseUrl . '/%3$s/%4$s">%4$s</a></td></tr>' . "\n",
                    $i, $file['id'], $thisDir['path'], $path, $checked);
            }
            print <<<EOH
</table>
<input type="submit" value="Ignore" />
</form>

EOH;
        }
    }

    public static function ignoreExtension(IManxDatabase $manxDb, $extension)
    {
        $format = $manxDb->getFormatForExtension($extension);
        $imageFormats = array('TIFF' => 1, 'PNG' => 1, 'JPEG' => 1, 'GIF' => 1);
        return strlen($format) == 0 || array_key_exists($format, $imageFormats);
    }

    public static function escapeSpecialChars($path)
    {
        return str_replace(" ", "%20", str_replace("#", urlencode("#"), $path));
    }
}
