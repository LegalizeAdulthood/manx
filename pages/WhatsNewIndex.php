<?php

require_once 'Config.php';
require_once 'IWhatsNewIndex.php';

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
        $transfer->get(PRIVATE_DIR . $this->_indexByDateFile);
        $this->_manxDb->setProperty($this->_timeStampProperty, $this->_factory->getCurrentTime());
    }

    public function parseIndexByDateFile()
    {
        $indexByDate = $this->_fileSystem->openFile(PRIVATE_DIR . $this->_indexByDateFile, 'r');
        $paths = [];
        while (!$indexByDate->eof())
        {
            $line = substr(trim($indexByDate->getString()), 20);
            if ($line !== false)
            {
                array_push($paths, $line);
            }
        }
        $this->_manxDb->addSiteUnknownPaths($this->_siteName, $paths);
    }

    private function pathUnknown($line)
    {
        $url = $this->_baseUrl . '/' . self::escapeSpecialChars($line);
        return $this->_manxDb->copyExistsForUrl($url) === false
            && $this->_manxDb->siteIgnoredPath($this->_siteName, $line) === false;
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
