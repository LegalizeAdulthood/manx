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
    private $_limit;

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
        $this->_user = $config['user'];
        $this->_limit = 100;
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
                $this->log('Path: ' . $path);
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
                $this->log('Path: ' . $path);
            }
        }
    }

    public function updateWhatsNewIndex()
    {
        if ($this->_whatsNewIndex->needIndexByDateFile())
        {
            $this->log('Updating WhatsNew.txt for site ' . $this->_siteName);
            $this->_whatsNewIndex->getIndexByDateFile();
            $this->_whatsNewIndex->parseIndexByDateFile();
        }
    }

    public function removeUnknownPathsWithCopy()
    {
        $this->log('Purging unknown paths with known copies.');
        $this->_db->removeUnknownPathsWithCopy();
    }

    public function ingest()
    {
        $this->log("Ingesting unknown paths for sites with IndexByDate.txt");
        $pubIds = [];
        $count = 0;
        foreach ($this->_db->getUnknownPathsForCompanies() as $row)
        {
            $count++;
            if ($count > $this->_limit)
            {
                return;
            }
            $this->_db->markUnknownPathScanned($row['id']);
            $siteId = $row['site_id'];
            $companyId = $row['company_id'];
            $url = $row['url'];
            $this->log(sprintf("Scanned:     %d.%d %s %s", $siteId, $companyId, $row['directory'], $url));

            // Don't need to re-extract data[url] because we're working from the site's base copy URL.
            // The url argument to determineData will never be a mirror URL.
            $data = $this->_urlMetaData->determineIngestData($siteId, $companyId, $url);
            if (!is_array($data) || !array_key_exists('part', $data) || !array_key_exists('title', $data) || !array_key_exists('pub_date', $data))
            {
                $this->log(sprintf("Skipped:     Couldn't identify document %d.%d %s", $siteId, $companyId, $url));
                continue;
            }
            if (array_key_exists('exists', $data))
            {
                $this->log(sprintf('Skipped:     Copy already exists "%s".', $data['title']));
                continue;
            }
            $part = $data['part'];
            if (strlen($part) == 0)
            {
                $this->log("Skipped:     Couldn't guess part number.");
                continue;
            }
            $title = $data['title'];
            $pubDate = $data['pub_date'];
            if (strlen($pubDate) == 0)
            {
                $this->log("Skipped:     Couldn't guess publication date.");
                continue;
            }

            // Conservatively ingest only documents where we could guess most metadata.
            $pubs = $data['pubs'];
            $numPubs = count($pubs);
            if ($numPubs > 1)
            {
                $this->log(sprintf("MultiPubs:   %d", $numPubs));
            }
            else if ($numPubs == 1)
            {
                $pubPart = $pubs[0]['ph_part'];
                $this->log(sprintf("OnePub:      %s (%s %s)", $pubs[0]['ph_title'], $pubPart, $pubPart == $data['part'] ? "match" : "no match"));
                $data['pub_id'] = $pubs[0]['pub_id'];
                if ($pubPart == $data['part'])
                {
                    $this->addCopy($data, $row);
                }
            }
            else if ($numPubs == 0 && !array_key_exists('exists', $data) && strlen($part) > 0 && strlen($title) > 0 && strlen($pubDate) > 0)
            {
                continue;

                $pubType = 'D';
                $altPart = '';
                $revision = '';
                $keywords = '';
                $notes = '';
                $abstract = '';
                $languages = '';
                $pubId = $this->_manx->addPublication($this->_user, $companyId, $part, $pubDate,
                    $title, $pubType, $altPart, $revision,
                    $keywords, $notes, $abstract, $languages);
                $data['pub_id'] = $pubId;
                $this->log(sprintf('Publication: %d.%d %s %s "%s" (%s)', $siteId, $pubId, $row['directory'], $pubDate, $title, $part));
                $pubIds[] = $pubId;

                $this->addCopy($data, $row);
            }
        }
    }

    private function addCopy($data, $row)
    {
        $pubId = $data['pub_id'];
        $format = 'PDF';
        $siteId = $row['site_id'];
        $url = $row['url'];
        $copyNotes = '';
        $copySize = $data['size'];
        $copyMD5 = '';
        $credits = '';
        $amendSerial = '';
        $copyId = $this->_db->addCopy($pubId, $format, $siteId, $url,
            $copyNotes, $copySize, $copyMD5, $credits, $amendSerial);
        $this->log(sprintf('Copy:        %d.%d %s %s "%s" (%s)', $siteId, $copyId, $row['directory'], $data['pub_date'], $data['title'], $data['part']));
    }

    private static function escapeSpecialChars($path)
    {
        return str_replace("#", urlencode("#"), $path);
    }

    private function log($text)
    {
        $this->_logger->log($text);
    }
}