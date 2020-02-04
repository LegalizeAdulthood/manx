<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

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
        $this->_limit = 500;
    }

    public function removeNonExistentUnknownPaths()
    {
        $this->log("Remove non-existent unknown paths");
        foreach($this->_db->getAllSiteUnknownPaths($this->_siteName) as $row)
        {
            $path = $row['path'];
            $url = $this->_baseCheckUrl . self::escapeSpecialChars($path);
            $urlInfo = $this->_factory->createUrlInfo($url);
            if (!$urlInfo->exists())
            {
                $this->_db->removeSiteUnknownPathById($row['id']);
                $this->log('Path: ' . $path);
            }
        }
    }

    public function updateMovedFiles()
    {
        $this->log("Updating location of moved files for " . $this->_siteName);
        foreach($this->_db->getPossiblyMovedSiteUnknownPaths($this->_siteName) as $row)
        {
            $path = $row['path'];
            $urlInfo = $this->_factory->createUrlInfo($this->_baseCheckUrl . $path);
            if ($urlInfo->exists() && $row['md5'] != '')
            {
                if ($urlInfo->md5() == $row['md5'])
                {
                    $this->_db->siteFileMoved($row['path_id'], $row['copy_id'], $this->_baseUrl . $path);
                    $this->log('Path: ' . $path);
                }
            }
        }
    }

    public function updateWhatsNewIndex()
    {
        if ($this->_whatsNewIndex->needIndexByDateFile())
        {
            $this->log('Updating IndexByDate.txt for site ' . $this->_siteName);
            $this->_whatsNewIndex->getIndexByDateFile();
            $this->_whatsNewIndex->parseIndexByDateFile();
        }
    }

    public function removeUnknownPathsWithCopy()
    {
        $this->log('Purging unknown paths with known copies.');
        $this->_db->removeUnknownPathsWithCopy();
    }

    public function computeMissingMD5()
    {
        $this->log("Computing missing MD5 hashes for known copies.");
        foreach ($this->_db->getAllMissingMD5Documents() as $row)
        {
            $url = self::escapeSpecialChars($row['url']);
            $urlInfo = $this->_factory->createUrlInfo($url);
            $md5 = '';
            if ($urlInfo->exists())
            {
                $md5 = $urlInfo->md5();
            }
            $this->_db->updateMD5ForCopy($row['copy_id'], $md5);
            $this->log(sprintf("%d %s %s", $row['copy_id'], $url, $md5 != '' ? $md5 : '<missing>'));
        }
    }

    public function updateIgnoredUnknownDirs()
    {
        $this->log("Updating ignored unknown directories");
        $this->_db->updateIgnoredUnknownDirs();
    }

    public function updateCopySiteUnknownDirIds()
    {
        $this->log("Updating site unknown directory ids for copies");
        $this->_db->updateCopySiteUnknownDirIds();
    }

    public function ingest()
    {
        $this->log("Ingesting unknown paths for sites with IndexByDate.txt");
        $pubIds = [];
        $count = 0;
        $ingestCount = 0;
        foreach ($this->_db->getUnknownPathsForCompanies($this->_siteName) as $row)
        {
            ++$count;
            if ($count >= $this->_limit)
            {
                break;
            }

            $this->_db->markUnknownPathScanned($row['id']);
            $siteId = $row['site_id'];
            $companyId = $row['company_id'];
            $url = $row['url'];
            $this->log(sprintf("Scanned:     %d.%d %s", $siteId, $companyId, $url));

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

            $this->log(sprintf("Meta:        Date %s, Part %s", $pubDate, $part));

            // Conservatively ingest only documents where we could guess most metadata.
            $pubs = $data['pubs'];
            $numPubs = count($pubs);
            if ($numPubs > 1)
            {
                $this->log(sprintf("MultiPubs:   %d", $numPubs));
                foreach ($pubs as $pub)
                {
                    $pubPart = $pub['ph_part'];
                    if ($pubPart == $part && $pubDate == $pub['ph_pub_date'])
                    {
                        $this->log(sprintf("PubMatch:    %s %s (%s %s)", $pubs[0]['ph_title'], $pubPart, $pubDate, $pubPart == $data['part'] ? "match" : "no match"));
                        $data['pub_id'] = $pub['pub_id'];
                        $this->addCopy($data, $row);
                        ++$ingestCount;
                        break;
                    }
                }
            }
            else if ($numPubs == 1)
            {
                $pubPart = $pubs[0]['ph_part'];
                $this->log(sprintf("OnePub:      %s (%s %s)", $pubs[0]['ph_title'], $pubPart, $pubPart == $data['part'] ? "match" : "no match"));
                $data['pub_id'] = $pubs[0]['pub_id'];
                if ($pubPart == $data['part'])
                {
                    $this->addCopy($data, $row);
                    ++$ingestCount;
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
                $this->log(sprintf('Publication: %d.%d %s "%s" (%s)', $siteId, $pubId, $pubDate, $title, $part));
                $pubIds[] = $pubId;

                $this->addCopy($data, $row);
                ++$ingestCount;
            }
        }

        $this->log(sprintf('Ingestion:   %d scanned, %d ingested (%0.2f%%)', $count, $ingestCount, $count > 0 ? 100*($ingestCount/$count) : 0));
    }

    private function addCopy($data, $row)
    {
        $pubId = $data['pub_id'];
        $format = 'PDF';
        $siteId = $row['site_id'];
        $url = $row['url'];
        $copyNotes = '';
        $copySize = $data['size'];
        $copyMD5 = $this->_urlMetaData->getCopyMD5($url);
        $credits = '';
        $amendSerial = '';
        $copyId = $this->_db->addCopy($pubId, $format, $siteId, $url,
            $copyNotes, $copySize, $copyMD5, $credits, $amendSerial);
        $this->log(sprintf('Copy:        %d.%d %s "%s" (%s)', $siteId, $copyId, $data['pub_date'], $data['title'], $data['part']));
    }

    private static function escapeSpecialChars($url)
    {
        $replacements = [
            ' ' => '%20',
            '#' => urlencode('#'),
            '&' => urlencode('&')
        ];
        foreach (array_keys($replacements) as $special)
        {
            $url = str_replace($special, $replacements[$special], $url);
        }
        return $url;
    }

    private function log($text)
    {
        $this->_logger->log($text);
    }
}
