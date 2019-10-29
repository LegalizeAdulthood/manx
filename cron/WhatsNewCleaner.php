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
    /** @var IUrlMetaData */
    private $_urlMetaData;

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
        $this->_urlMetaData = $config['urlMetaData'];
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

    public function ingest()
    {
        $user = $this->_manx->getUserFromSession();
        $pubIds = [];
        foreach ($this->_db->getUnknownPathsForCompanies() as $row)
        {
            $url = $row['url'];
            // Don't need to re-extract data[url] because we're working from the site's base copy URL.
            // The url argument to determineData will never be a mirror URL.
            $data = $this->_urlMetaData->determineData($url);
            $part = $data['part'];
            $title = $data['title'];
            $pubDate = $data['pub_date'];

            // Conservatively ingest only documents where we could guess most metadata.
            if (strlen($part) > 0 && strlen($title) > 0 && strlen($pubDate) > 0)
            {
                $siteId = $row['site_id'];
                $companyId = $row['company_id'];
                $file = $row['file'];
                $pubType = 'D';
                $altPart = '';
                $revision = '';
                $keywords = '';
                $notes = '';
                $abstract = '';
                $languages = '';
                $pubId = $this->_manx->addPublication($user, $companyId, $part, $pubDate, $title, $pubType, $altPart, $revision, $keywords, $notes, $abstract, $languages);
                $pubIds[] = $pubId;

                $format = $data['format'];
                $copyNotes = '';
                $copySize = $data['size'];
                $copyMD5 = '';
                $credits = '';
                $amendSerial = '';
                $this->_db->addCopy($pubId, $format, $siteId, $url, $copyNotes, $copySize, $copyMD5, $credits, $amendSerial);
            }
        }
    }

    private static function escapeSpecialChars($path)
    {
        return str_replace("#", urlencode("#"), $path);
    }
}
