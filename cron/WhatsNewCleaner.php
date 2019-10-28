<?php

require_once 'pages/IManx.php';
require_once 'cron/ILogger.php';
require_once 'cron/IWhatsNewCleaner.php';

use Pimple\Container;

class WhatsNewCleaner implements IWhatsNewCleaner
{
    private $_manx;
    private $_db;
    private $_factory;
    /** @var ILogger */
    private $_logger;
    /** @var string */
    private $_siteName;
    private $_whatsNewIndex;

    private static function endsWith($str, $needle)
    {
        if (strlen($str) < strlen($needle))
        {
            return false;
        }
        return substr($str, -strlen($needle)) == $needle;
    }

    private static function ensureTrailingSlash($url)
    {
        return self::endsWith($url, '/') ? $url : $url . '/';
    }

    public function __construct(Container $config)
    {
        $this->_manx = $config['manx'];
        $this->_db = $this->_manx->getDatabase();
        $this->_factory = $config['whatsNewPageFactory'];
        $this->_logger = $config['logger'];
        $this->_siteName = $config['siteName'];
        $this->_baseCheckUrl = self::ensureTrailingSlash($config['baseCheckUrl']);
        $this->_baseUrl = self::ensureTrailingSlash($config['baseUrl']);
        $this->_whatsNewIndex = $config['whatsNewIndex'];
    }

    public function removeNonExistentUnknownPaths()
    {
        foreach($this->_db->getAllSiteUnknownPaths($this->_siteName) as $row)
        {
            $path = $row['path'];
            $url = $this->_baseCheckUrl . self::escapeSpecialChars($path);
            $urlInfo = $this->_factory->createUrlInfo($url);
            if (!$urlInfo->exists())
            {
                $this->_db->removeSiteUnknownPathById($this->_siteName, $row['id']);
                $this->_logger->log('Path: ' . $path);
            }
        }
    }

    public function updateMovedFiles()
    {
        foreach($this->_db->getPossiblyMovedSiteUnknownPaths($this->_siteName) as $row)
        {
            $path = $row['path'];
            $urlInfo = $this->_factory->createUrlInfo($this->_baseCheckUrl . $path);
            if ($urlInfo->md5() == $row['md5'])
            {
                $this->_db->siteFileMoved($this->_siteName, $row['copy_id'], $row['path_id'], $this->_baseUrl . $path);
                $this->_logger->log('Path: ' . $path);
            }
        }
    }

    public function updateWhatsNewIndex()
    {
        if ($this->_whatsNewIndex->needIndexByDateFile())
        {
            $this->_logger->log('Updating WhatsNew.txt for site ' . $this->_siteName);
            $this->_whatsNewIndex->getIndexByDateFile();
            $this->_whatsNewIndex->parseIndexByDateFile();
        }
    }

    public function removeUnknownPathsWithCopy()
    {
        $this->_logger->log('Purging unknown paths with known copies.');
        $this->_db->removeUnknownPathsWithCopy();
    }

    private static function escapeSpecialChars($path)
    {
        return str_replace("#", urlencode("#"), $path);
    }
}
