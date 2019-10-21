<?php

require_once 'pages/IManx.php';

use Pimple\Container;

interface ILogger
{
    public function log($line);
}

class Logger implements ILogger
{
    function log($line)
    {
        print($line . "\n");
    }
}

class BitSaversCleaner
{
    private $_manx;
    private $_db;
    private $_factory;
    private $_logger;

    public function __construct(Container $config)
    {
        $this->_manx = $config['manx'];
        $this->_db = $this->_manx->getDatabase();
        $this->_factory = $config['whatsNewPageFactory'];
        $this->_logger = $config['logger'];
    }

    public function removeNonExistentUnknownPaths()
    {
        foreach($this->_db->getAllSiteUnknownPaths('bitsavers') as $row)
        {
            $path = $row['path'];
            $url = 'http://bitsavers.trailing-edge.com/pdf/' . self::escapeSpecialChars($path);
            $urlInfo = $this->_factory->createUrlInfo($url);
            if (!$urlInfo->exists())
            {
                $this->_db->removeSiteUnknownPathById('bitsavers', $row['id']);
                $this->_logger->log('Path: ' . $path);
            }
        }
    }

    public function updateMovedFiles()
    {
        foreach($this->_db->getPossiblyMovedSiteUnknownPaths('bitsavers') as $row)
        {
            $path = $row['path'];
            $urlInfo = $this->_factory->createUrlInfo('http://bitsavers.trailing-edge.com/pdf/' . $path);
            if ($urlInfo->md5() == $row['md5'])
            {
                $this->_db->siteFileMoved('bitsavers', $row['copy_id'], $row['path_id'], 'http://bitsavers.org/pdf/' . $path);
                $this->_logger->log('Path: ' . $path);
            }
        }
    }

    private static function escapeSpecialChars($path)
    {
        return str_replace("#", urlencode("#"), $path);
    }
}
