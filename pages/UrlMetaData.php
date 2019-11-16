<?php

require_once 'pages/IManxDatabase.php';
require_once 'pages/IUrlInfoFactory.php';
require_once 'pages/IUrlMetaData.php';
require_once 'pages/Site.php';

use Pimple\Container;

class UrlMetaData implements IUrlMetaData
{
    /** @var IManxDatabase */
    private $_db;
    /** @var IUrlInfoFactory */
    private $_urlInfoFactory;

    public function __construct(Container $config)
    {
        $this->_db = $config['db'];
        $this->_urlInfoFactory = $config['urlInfoFactory'];
    }

    public function getCopyMD5($url)
    {
        $urlInfo = $this->_urlInfoFactory->createUrlInfo($url);
        return $urlInfo->md5();
    }

    public function determineIngestData($siteId, $companyId, $url)
    {
        $urlInfo = $this->_urlInfoFactory->createUrlInfo($url);
        $size = $urlInfo->size();
        if ($size === false)
        {
            $data['valid'] = false;
            return $data;
        }

        $data['url'] = $url;
        $data['size'] = $size;
        $data['valid'] = true;
        $this->_sites = $this->_db->getSites();
        $data['site'] = $this->getMatchingSite($siteId);
        $data['company'] = $companyId;
        $data['part'] = '';
        $data['pub_date'] = '';
        $data['title'] = '';
        if ($this->siteIsBitSavers($data))
        {
            $this->determineIngestBitSaversData($data);
        }
        else if ($this->siteIsChiClassicComp($data))
        {
            $this->determineIngestChiClassicCompData($data);
        }
        else
        {
            $this->determineUrlData($data);
        }
        $this->determineUrlExists($data);
        unset($data['url']);
        return $data;
    }

    private function getMatchingSite($siteId)
    {
        foreach ($this->_sites as $site)
        {
            if ($site['site_id'] == $siteId)
            {
                return $site;
            }
        }
        throw new Exception("Couldn't find site for id " . $siteId);
    }

    public function determineData($url)
    {
        $urlInfo = $this->_urlInfoFactory->createUrlInfo($url);
        $size = $urlInfo->size();
        if ($size === false)
        {
            $data['valid'] = false;
            return $data;
        }

        $data['url'] = $url;
        $data['mirror_url'] = '';
        $data['size'] = $size;
        $data['valid'] = true;
        $this->_sites = $this->_db->getSites();
        $data['site'] = $this->determineSiteFromUrl($data);
        $data['company'] = -1;
        $data['part'] = '';
        $data['pub_date'] = '';
        $data['title'] = '';
        $data['format'] = '';
        if ($this->siteIsBitSavers($data))
        {
            $this->determineBitSaversData($data);
        }
        else if ($this->siteIsChiClassicComp($data))
        {
            $this->determineChiClassicCompData($data);
        }
        else
        {
            $this->determineUrlData($data);
        }
        $this->determineUrlExists($data);
        return $data;
    }

    private function determineUrlExists(&$data)
    {
        $row = $this->_db->copyExistsForUrl($data['url']);
        if ($row)
        {
            $data['exists'] = true;
            $data['company'] = $row['ph_company'];
            $data['pub_id'] = $row['ph_pub'];
            $data['title'] = $row['ph_title'];
        }
    }

    private function siteIsBitSavers($data)
    {
        return $this->siteMatchesId($data, Site::BitSavers);
    }

    private function siteIsChiClassicComp($data)
    {
        return $this->siteMatchesId($data, Site::ChiClassicComp);
    }

    private function siteMatchesId($data, $siteId)
    {
        return array_key_exists('site_id', $data['site'])
            && $data['site']['site_id'] == $siteId;
    }

    private function determineUrlData(&$data)
    {
        $url = $data['url'];
        $matches = array();
        if (substr($url, strlen($url) - 4, 4) == '.tgz')
        {
            $url = substr($url, 0, strlen($url) - 4);
        }
        if (1 != preg_match('|^.*/([^/]+)\.([^./]+)$|', $url, $matches))
        {
            return;
        }
        list($data['pub_date'], $fileBase) = self::extractPubDate($matches[1]);
        $data['title'] = self::titleForFileBase($fileBase);
        $data['format'] = $this->_db->getFormatForExtension($matches[2]);
    }

    public static function extractPubDate($fileBase)
    {
        $pubDate = '';
        $parts = explode('_', $fileBase);
        if (count($parts) > 1)
        {
            $lastPart = count($parts)-1;
            $year = strtolower($parts[$lastPart]);
            if (is_numeric($year) && $year > 9)
            {
                if ($year < 100)
                {
                    $year += 1900;
                }
                $pubDate = $year;
                --$lastPart;

                $day = $parts[$lastPart];
                if ($lastPart > 1 && is_numeric($day) && $day > 0 && $day < 32)
                {
                    --$lastPart;
                }
                else
                {
                    $day = '';
                }

                if ($lastPart > 0)
                {
                    $month = self::matchMonth($parts[$lastPart]);
                    if ($month != '')
                    {
                        $pubDate = sprintf("%s-%s", $pubDate, $month);
                        --$lastPart;

                        if ($lastPart > 0 && strlen($day) == 0)
                        {
                            $day = $parts[$lastPart];
                            if (is_numeric($day) && $day > 0 && $day < 32)
                            {
                                --$lastPart;
                            }
                            else
                            {
                                $day = '';
                            }
                        }

                        if (strlen($day) > 0)
                        {
                            $pubDate = sprintf("%s-%02d", $pubDate, $day);
                        }
                    }
                    else if ($day != '')
                    {
                        ++$lastPart;
                    }
                }

                $fileBase = implode('_', array_slice($parts, 0, $lastPart + 1));
            }
            else if (1 == preg_match('/^([a-z]+)([0-9]+)$/', $year, $matches))
            {
                $year = $matches[2];
                if ($year < 100)
                {
                    $year += 1900;
                }
                $month = self::matchMonth(substr($matches[1], 0, 3));
                if ($month != '')
                {
                    $pubDate = sprintf("%d-%s", $year, $month);
                    --$lastPart;
                    $fileBase = implode('_', array_slice($parts, 0, $lastPart + 1));
                }
            }
        }
        return [$pubDate, $fileBase];
    }

    private static function matchMonth($text)
    {
        $text = strtolower($text);
        $months = self::months();
        foreach (array_keys($months) as $prefix)
        {
            if ($text == $prefix)
            {
                return $months[$text];
            }
        }

        $months = self::monthNames();
        foreach (array_keys($months) as $name)
        {
            if ($text == $name)
            {
                return $months[$text];
            }
        }

        return '';
    }

    private function findSiteById($id)
    {
        if ($id != -1)
        {
            foreach ($this->_sites as $site)
            {
                if ($site['site_id'] == $id)
                {
                    return $site;
                }
            }
        }
        return array();
    }

    private function determineSiteFromMirrorUrl(&$data)
    {
        $matchingPrefix = '';
        $matchingSite = -1;
        $originalPrefix = '';
        foreach ($this->_db->getMirrors() as $mirror)
        {
            $mirrorBase = $mirror['copy_stem'];
            if (substr($data['url'], 0, strlen($mirrorBase)) == $mirrorBase)
            {
                if (strlen($mirrorBase) > strlen($matchingPrefix))
                {
                    $matchingPrefix = $mirrorBase;
                    $matchingSite = $mirror['site'];
                    $originalPrefix = $mirror['original_stem'];
                }
            }
        }
        if ($matchingSite != -1)
        {
            $data['mirror_url'] = $data['url'];
            $data['url'] = $originalPrefix . substr($data['url'], strlen($matchingPrefix));
        }
        return $this->findSiteById($matchingSite);
    }

    private function determineSiteFromUrl(&$data)
    {
        $url = $data['url'];
        $matchingPrefix = '';
        $matchingSite = array();
        $components = parse_url($url);
        foreach ($this->_sites as $site)
        {
            $siteBase = $site['copy_base'];
            if (strlen($siteBase) == 0)
            {
                $siteBase = $site['url'];
            }
            $siteComponents = parse_url($siteBase);
            if (self::urlComponentsMatch($components, $siteComponents))
            {
                if (strlen($siteBase) > strlen($matchingPrefix))
                {
                    $matchingPrefix = $siteBase;
                    $matchingSite = $site;
                }
            }
        }
        if (count($matchingSite) == 0)
        {
            return $this->determineSiteFromMirrorUrl($data);
        }
        $siteComponents = parse_url($matchingSite['url']);
        $components['host'] = $siteComponents['host'];
        $data['url'] = self::buildUrl($components);
        return $matchingSite;
    }

    private static function buildUrl($components)
    {
        return sprintf("%s://%s%s", $components['scheme'], $components['host'], $components['path']);
    }

    private static function componentEqual($component, $lhs, $rhs)
    {
        return (!array_key_exists($component, $lhs) && !array_key_exists($component, $rhs))
            || (array_key_exists($component, $lhs)
                && array_key_exists($component, $rhs)
                && $lhs[$component] == $rhs[$component]);
    }

    public static function urlComponentsMatch($components, $siteComponents)
    {
        $path = $components['path'];
        $sitePath = $siteComponents['path'];
        if (strlen($sitePath) > strlen($path))
        {
            return false;
        }
        if (self::componentEqual('scheme', $components, $siteComponents)
            && self::componentEqual('port', $components, $siteComponents))
        {
            $hostEqual = false;
            if (self::componentEqual('host', $components, $siteComponents))
            {
                $hostEqual = true;
            }
            else
            {
                $host = explode('.', $components['host']);
                $siteHost = explode('.', $siteComponents['host']);
                if ((count($siteHost) == 2)
                    && (count($host) == 3)
                    && ($host[0] == 'www'))
                {
                    array_shift($host);
                    $hostEqual = implode('.', $host) == implode('.', $siteHost);
                }
            }
            if ($hostEqual
                && (substr($path, 0, strlen($sitePath)) == $sitePath))
            {
                return true;
            }
        }

        return false;
    }

    private static function months()
    {
        return ['jan' => '01', 'feb' => '02', 'mar' => '03',
            'apr' => '04', 'may' => '05', 'jun' => '06',
            'jul' => '07', 'aug' => '08', 'sep' => '09',
            'oct' => '10', 'nov' => '11', 'dec' => '12'];
    }

    private static function monthNames()
    {
        return ['january' => '01', 'february' => '02', 'march' => '03',
            'april' => '04', 'may' => '05', 'june' => '06',
            'july' => '07', 'august' => '08', 'september' => '09',
            'october' => '10', 'november' => '11', 'december' => '12'];
    }

    private function determineIngestSiteData($siteName, $companyComponent, &$data)
    {
        $url = $data['url'];
        $urlComponents = parse_url($url);
        $dirs = explode('/', $urlComponents['path']);
        $fileName = array_pop($dirs);
        $filePieces = explode('.', $fileName);
        array_pop($filePieces);
        $fileBase = implode('.', $filePieces);
        list($data['pub_date'], $fileBase) = self::extractPubDate($fileBase);

        $parts = explode('_', $fileBase);
        if (count($parts) > 1)
        {
            if (1 == preg_match('/[0-9][0-9]+/', $parts[0]))
            {
                $data['part'] = array_shift($parts);
            }
        }
        $data['title'] = self::titleForFileBase(implode(' ', $parts));
        $data['pubs'] = $this->_db->getPublicationsForPartNumber($data['part'], $data['company']);
    }

    private function determineIngestBitSaversData(&$data)
    {
        $this->determineIngestSiteData('bitsavers', 2, $data);
    }

    private function determineIngestChiClassicCompData(&$data)
    {
        $this->determineIngestSiteData('ChiClassicComp', 4, $data);
    }

    private function determineSiteData($siteName, $companyComponent, $parentDirComponent, &$data)
    {
        $url = $data['url'];
        $urlComponents = parse_url($url);
        $dirs = explode('/', $urlComponents['path']);
        $companyDir = $dirs[$companyComponent];
        $parentDir = $parentDirComponent == -1 ? '' : $dirs[$parentDirComponent];

        $company = $this->_db->getCompanyIdForSiteDirectory($siteName, $companyDir, $parentDir);
        $data['company'] = $company;
        $data['site_company_directory'] = $companyDir;
        $data['site_company_parent_directory'] = $parentDir;

        $fileName = array_pop($dirs);
        $dotPos = strrpos($fileName, '.');
        if ($dotPos === false)
        {
            $fileBase = $fileName;
            $extension = '';
        }
        else
        {
            $fileParts = explode('.', $fileName);
            $extension = array_pop($fileParts);
            $fileBase = implode('.', $fileParts);
        }
        list($data['pub_date'], $fileBase) = self::extractPubDate($fileBase);
        $parts = explode('_', $fileBase);
        if (count($parts) > 1)
        {
            if (1 == preg_match('/[0-9][0-9]+/', $parts[0]))
            {
                $data['part'] = array_shift($parts);
            }
            $data['pubs'] = $this->_db->getPublicationsForPartNumber($data['part'], $data['company']);
            $data['title'] = self::titleForFileBase(implode(' ', $parts));
        }
        else
        {
            $data['pubs'] = $this->findPublicationsForKeywords($company, array($fileBase));
            $data['title'] = self::titleForFileBase($fileBase);
        }
        $data['format'] = $this->_db->getFormatForExtension($extension);
    }

    private function determineBitSaversData(&$data)
    {
        $this->determineSiteData('bitsavers', 2, -1, $data);
    }

    private function determineChiClassicCompData(&$data)
    {
        $this->determineSiteData('ChiClassicComp', 4, 3, $data);
    }

    public static function titleForFileBase($fileBase)
    {
        $title = str_replace('_', ' ', str_replace(urlencode('#'), '#', $fileBase));
        if (1 == preg_match('/[a-z][A-Z]/', $title))
        {
            $words = array();
            $pieces = preg_split('/([a-z])([A-Z])/', $title, -1, PREG_SPLIT_DELIM_CAPTURE);
            $i = 0;
            array_push($words, $pieces[$i + 0] . $pieces[$i + 1]);
            $i += 2;
            for (; $i < count($pieces) - 2; $i += 3)
            {
                array_push($words, $pieces[$i + 0] . $pieces[$i + 1] . $pieces[$i + 2]);
            }
            array_push($words, $pieces[$i + 0] . $pieces[$i + 1]);
            $title = implode(' ', $words);
        }
        return $title;
    }

    private static function emptyStringIfNull($str)
    {
        return is_null($str) ? '' : $str;
    }

    private function accumulatePublicationsForKeywords($company, $keywords, $online)
    {
        $data = array();
        foreach ($this->_db->searchForPublications($company, $keywords, $online) as $row)
        {
            $pubId = $row['pub_id'];
            if (!array_key_exists($pubId, $data))
            {
                $data[$pubId] = array('pub_id' => $row['pub_id'],
                    'ph_part' => self::emptyStringIfNull($row['ph_part']),
                    'ph_revision' => self::emptyStringIfNull($row['ph_revision']),
                    'ph_title' => $row['ph_title']);
            }
        }
        return $data;
    }

    private function mergePubs($left, $right)
    {
        foreach (array_keys($right) as $pubId)
        {
            if (!array_key_exists($pubId, $left))
            {
                $left[$pubId] = $right[$pubId];
            }
        }
        return $left;
    }

    private function findPublicationsForKeywords($company, $keywords)
    {
        return $this->mergePubs(
            $this->accumulatePublicationsForKeywords($company, $keywords, false),
            $this->accumulatePublicationsForKeywords($company, $keywords, true));
    }
}
