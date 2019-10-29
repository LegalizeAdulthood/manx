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
            $months = self::months();
            if (is_numeric($year) && $year > 9)
            {
                if ($year < 100)
                {
                    $year += 1900;
                }
                $pubDate = $year;
                --$lastPart;
                $month = strtolower(substr($parts[$lastPart], 0, 3));
                foreach (array_keys($months) as $prefix)
                {
                    if ($month == $prefix)
                    {
                        $pubDate = sprintf("%s-%s", $pubDate, $months[$prefix]);
                        --$lastPart;
                        break;
                    }
                }
                $fileBase = implode('_', array_slice($parts, 0, $lastPart + 1));
            }
            else if (1 == preg_match('/^([a-z]+)([0-9]+)$/', $year, $matches))
            {
                $month = strtolower(substr($matches[1], 0, 3));
                $year = $matches[2];
                if ($year < 100)
                {
                    $year += 1900;
                }
                foreach (array_keys($months) as $prefix)
                {
                    if ($month == $prefix)
                    {
                        $pubDate = sprintf("%d-%s", $year, $months[$prefix]);
                        --$lastPart;
                        $fileBase = implode('_', array_slice($parts, 0, $lastPart + 1));
                        break;
                    }
                }
            }
        }
        return array($pubDate, $fileBase);
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
        return array('jan' => '01', 'feb' => '02', 'mar' => '03',
            'apr' => '04', 'may' => '05', 'jun' => '06',
            'jul' => '07', 'aug' => '08', 'sep' => '09',
            'oct' => '10', 'nov' => '11', 'dec' => '12');
    }

    private function determineSiteData($siteName, $companyComponent, &$data)
    {
        $url = $data['url'];
        $urlComponents = parse_url($url);
        $dirs = explode('/', $urlComponents['path']);
        $companyDir = $dirs[$companyComponent];

        $company = $this->_db->getCompanyForSiteDirectory($siteName, $companyDir);
        $data['company'] = $company;
        $data['site_company_directory'] = $companyDir;

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
        $this->determineSiteData('bitsavers', 2, $data);
    }

    private function determineChiClassicCompData(&$data)
    {
        $this->determineSiteData('ChiClassicComp', 4, $data);
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
