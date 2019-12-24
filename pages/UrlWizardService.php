<?php

namespace Manx;

require_once 'vendor/autoload.php';

use Pimple\Container;

/*

1. Enter URL and submit to wizard for analysis.
2. Wizard does the following:
   a. Look at prefix of URL and try to associate a mirror or site with
      the URL.
   b. Look at directory components of the URL and try to match them
      against short names for companies to identify the company.
   c. Look at the prefix of the last component of the URL and attempt to
      extract a proposed part number
   d. Fetch document and compute size and MD5 (requires PECL http)
   e. Extract a proposed title from the last component of the URL
   f. Identify a possible new site if URL doesn't match any known site
      or mirror.
3. Show all guesses at information for editing before submitting a
   real change.

*/

class UrlWizardService extends ServicePageBase
{
    /** @var IUrlMetaData */
    private $_meta;

    public function __construct(Container $config)
    {
        parent::__construct($config);
        $this->_meta = $config['urlMetaData'];
    }

    private function determineData()
    {
        $url = $this->param('url');
        return $this->_meta->determineData($url);
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

    private function findPublicationsForKeywords($company, $keywords)
    {
        return $this->mergePubs(
            $this->accumulatePublicationsForKeywords($company, $keywords, false),
            $this->accumulatePublicationsForKeywords($company, $keywords, true));
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

    private function findPublications()
    {
        $company = $this->param('company');
        $ignoredWords = array();
        $keywords = Searcher::filterSearchKeywords($this->param('keywords'), $ignoredWords);
        if (count($keywords))
        {
            $data = $this->findPublicationsForKeywords($company, $keywords);
            $filtered = Searcher::filterSearchKeywords($keywords[0], $ignoredWords);
            if (count($filtered))
            {
                $data = $this->mergePubs($data, $this->findPublicationsForKeywords($company, $filtered));
            }
            $data = array_values($data);
            usort($data, ['Manx\UrlWizardService', 'comparePublications']);
        }
        else
        {
            $data = array();
        }
        return $data;
    }

    protected function renderJsonResponse($data)
    {
        $this->header("Content-Type: application/json; charset=utf-8");
        print json_encode($data);
    }

    protected function header($field)
    {
        header($field);
    }

    protected function methodDispatch()
    {
        $method = $this->param('method');
        if ($method == 'url-lookup')
        {
            $this->renderJsonResponse($this->determineData());
            return true;
        }
        else if ($method == 'pub-search')
        {
            $this->renderJsonResponse($this->findPublications());
            return true;
        }

        return false;
    }

    public static function comparePublications($left, $right)
    {
        $result = strcmp($left['ph_part'], $right['ph_part']);
        if ($result == 0)
        {
            $result = strcmp($left['ph_revision'], $right['ph_revision']);
        }
        if ($result == 0)
        {
            $result = strcmp($left['ph_title'], $right['ph_title']);
        }
        return ($result < 0) ? -1 : ($result > 0 ? 1 : 0);
    }
}
