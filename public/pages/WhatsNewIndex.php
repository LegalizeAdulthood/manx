<?php

namespace Manx;

use Pimple\Container;

class WhatsNewIndex implements IWhatsNewIndex
{
    public function __construct(Container $config)
    {
        $this->_manxDb = $config['manx']->getDatabase();
        $this->_timeStampProperty = $config['timeStampProperty'];
        $this->_indexByDateUrl = $config['indexByDateUrl'];
        $this->_indexByDateFile = $config['indexByDateFile'];
        $this->_baseUrl = $config['baseUrl'];
        $this->_siteName = $config['siteName'];
        $this->_fileSystem = $config['fileSystem'];
        $this->_factory = $config['whatsNewPageFactory'];
    }

    public function needIndexByDateFile()
    {
        $timeStamp = $this->_manxDb->getProperty($this->_timeStampProperty);
        if ($timeStamp === false)
        {
            return true;
        }
        $urlInfo = $this->_factory->createUrlInfo($this->_indexByDateUrl);
        $lastModified = $urlInfo->lastModified();
        if ($lastModified === false)
        {
            $lastModified = $this->_factory->getCurrentTime();
        }
        $this->_manxDb->setProperty($this->_timeStampProperty, $lastModified);
        return $lastModified > $timeStamp;
    }

    public function getIndexByDateFile()
    {
        $transfer = $this->_factory->createUrlTransfer($this->_indexByDateUrl);
        $transfer->get(Config::configFile($this->_indexByDateFile));
        $this->_manxDb->setProperty($this->_timeStampProperty, $this->_factory->getCurrentTime());
    }

    public function parseIndexByDateFile()
    {
        $indexByDate = $this->_fileSystem->openFile(Config::configFile($this->_indexByDateFile), 'r');
        $paths = [];
        while (!$indexByDate->eof())
        {
            $line = trim($indexByDate->getString());
            if ($line == '')
            {
                continue;
            }
            $path = substr($line, 20);
            if ($path !== false && $path != '')
            {
                array_push($paths, $path);
            }
        }
        $this->_manxDb->addSiteUnknownPaths($this->_siteName, $paths);
    }

    private static function escapeSpecialChars($path)
    {
        return str_replace("#", urlencode("#"), $path);
    }

    private $_manxDb;
    private $_factory;
    private $_timeStampProperty;
    private $_indexByDateUrl;
    private $_indexByDateFile;
    private $_baseUrl;
    private $_siteName;
}
