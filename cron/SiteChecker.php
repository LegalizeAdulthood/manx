<?php

namespace Manx\Cron;

use Pimple\Container;

class SiteChecker
{
    public function __construct(Container $config)
    {
        $this->_db = $config['manx']->getDatabase();
        $this->_factory = $config['urlInfoFactory'];
        $this->_logger = $config['logger'];
    }

    public function checkSites()
    {
        $this->log("Checking online status of sites");
        foreach ($this->_db->getSites() as $site)
        {
            $siteUrl = $site['url'];
            $urlInfo = $this->_factory->createUrlInfo($siteUrl);
            $online = false;
            if ($urlInfo->exists())
            {
                if ($siteUrl != $urlInfo->url())
                {
                    $this->log(sprintf("Redirect site %s to %s", $siteUrl, $urlInfo->url()));
                }

                $anyDocExists = false;
                foreach ($this->_db->getSampleCopiesForSite($site['site_id']) as $doc)
                {
                    $docInfo = $this->_factory->createUrlInfo($doc['url']);
                    if (!$docInfo->exists())
                    {
                        $this->log(sprintf("     offline %s", $doc['url']));
                    }
                    else
                    {
                        $this->log(sprintf("      online %s", $doc['url']));
                        $anyDocExists = true;
                        break;
                    }
                }
                $online = $anyDocExists;
            }
            $this->_db->setSiteLive($site['site_id'], $online);
            $this->log(sprintf("Site %sline %s (%s)", $online ? " on" : "off", $site['name'], $site['url']));
        }
    }

    private function log($text)
    {
        $this->_logger->log($text);
    }

    /** @var IManxDatabase */
    private $_db;
    /** @var IUrlInfoFactory */
    private $_factory;
    /** @var ILogger */
    private $_logger;
}
