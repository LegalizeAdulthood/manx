<?php

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

require_once 'Manx.php';
require_once 'Searcher.php';
require_once 'ServicePageBase.php';

class Site
{
	const BitSavers = 3;
}

class UrlWizardService extends ServicePageBase
{
	/** @var array */
	private $_sites;

	private function determineData()
	{
		$data['url'] = $this->param('url');
		$this->_sites = $this->_db->getSites();
		$data['site'] = $this->determineSiteFromUrl($data);
		$data['company'] = -1;
		$data['part'] = '';
		$data['pub_date'] = '';
		$data['title'] = '';
		$data['format'] = '';
		if (array_key_exists('siteid', $data['site'])
			&& $data['site']['siteid'] == Site::BitSavers)
		{
			$this->determineBitSaversData($data);
		}
		else
		{
			$this->determineUrlData($data);
		}
		return $data;
	}

	private function determineUrlData(&$data)
	{
		$url = $data['url'];
		$matches = array();
		if (1 != preg_match('|^.*/([^/]+)\.([^./]+)$|', $url, $matches))
		{
			return;
		}
		list($data['pub_date'], $fileBase) = UrlWizardService::extractPubDate($matches[1]);
		$data['title'] = implode(' ', explode('_', $fileBase));
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
			$months = UrlWizardService::months();
			if (is_numeric($year))
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
				if ($site['siteid'] == $id)
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
			if (UrlWizardService::urlComponentsMatch($components, $siteComponents))
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
		$data['url'] = UrlWizardService::buildUrl($components);
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
		if (UrlWizardService::componentEqual('scheme', $components, $siteComponents)
			&& UrlWizardService::componentEqual('port', $components, $siteComponents))
		{
			$hostEqual = false;
			if (UrlWizardService::componentEqual('host', $components, $siteComponents))
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

	private function determineSiteForHost($host)
	{
		$matchingPrefix = '';
		$matchingSite = array();
		foreach ($this->_sites as $site)
		{
			$siteBase = $site['copy_base'];
			if (strlen($siteBase) == 0)
			{
				$siteBase = $site['url'];
			}
			$siteComponents = parse_url($siteBase);
			if ($host == $siteComponents['host'])
			{
				if (strlen($siteBase) > strlen($matchingPrefix))
				{
					$matchingPrefix = $siteBase;
					$matchingSite = $site;
				}
			}
		}
		return $matchingSite;
	}

	private static function months()
	{
		return array('jan' => '01', 'feb' => '02', 'mar' => '03',
			'apr' => '04', 'may' => '05', 'jun' => '06',
			'jul' => '07', 'aug' => '08', 'sep' => '09',
			'oct' => '10', 'nov' => '11', 'dec' => '12');
	}

	private function determineBitSaversData(&$data)
	{
		$url = $data['url'];
		$urlComponents = parse_url($url);
		$dirs = explode('/', $urlComponents['path']);
		$companyDir = $dirs[2];

		$company = $this->_db->getCompanyForBitSaversDirectory($companyDir);
		$data['company'] = $company;
		$data['bitsavers_directory'] = $companyDir;

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
		list($data['pub_date'], $fileBase) = UrlWizardService::extractPubDate($fileBase);
		$parts = explode('_', $fileBase);
		if (count($parts) > 1)
		{
			if (1 == preg_match('/[0-9][0-9]+/', $parts[0]))
			{
				$data['part'] = array_shift($parts);
			}
			$lastPart = count($parts)-1;
			$data['pubs'] = $this->_db->getPublicationsForPartNumber($data['part'], $data['company']);
			$data['title'] = implode(' ', $parts);
		}
		$data['format'] = $this->_db->getFormatForExtension($extension);
	}

	private function findPublicationsForKeywords($keywords)
	{
		$data = array();
		foreach ($this->_db->searchForPublications($this->param('company'), $keywords, false) as $row)
		{
			array_push($data,
				array('pub_id' => $row['pub_id'],
					'ph_part' => $row['ph_part'],
					'ph_revision' => $row['ph_revision'],
					'ph_title' => $row['ph_title']));
		}
		return $data;
	}

	private function findPublications()
	{
		$company = $this->param('company');
		$ignoredWords = array();
		$keywords = Searcher::filterSearchKeywords($this->param('keywords'), $ignoredWords);
		$data = $this->findPublicationsForKeywords($keywords);
		foreach ($keywords as $keyword)
		{
			if (1 == preg_match('|^([0-9]+)|', $keyword, $matches))
			{
				$filtered = Searcher::filterSearchKeywords($matches[1], $ignoredWords);
				if (count($filtered))
				{
					$data = array_merge($data, $this->findPublicationsForKeywords($filtered));
				}
			}
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
}

?>
