<?php

namespace Manx;

use Pimple\Container;

class WhatsNewIndex implements IWhatsNewIndex
{
    const INDEX_BATCH_SIZE = 500;
    const EMPTY_INDEX_MAX_RETRIES = 15;
    const EMPTY_INDEX_RETRY_SECONDS = 15;

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
        $this->_logger = isset($config['logger']) ? $config['logger'] : null;
    }

    public function needIndexByDateFile()
    {
        $timeStamp = $this->_manxDb->getProperty($this->_timeStampProperty);
        if ($timeStamp === false)
        {
            return true;
        }
        if (!$this->_fileSystem->fileExists(Config::configFile($this->_indexByDateFile)))
        {
            return true;
        }
        $urlInfo = $this->_factory->createUrlInfo($this->_indexByDateUrl);
        $lastModified = $urlInfo->lastModified();
        if ($lastModified === false)
        {
            $lastModified = $this->_factory->getCurrentTime();
        }
        return $lastModified > $timeStamp;
    }

    public function getIndexByDateFile()
    {
        if (!$this->waitForNonEmptyIndexByDateFile())
        {
            return false;
        }

        $transfer = $this->_factory->createUrlTransfer($this->_indexByDateUrl);
        if (!$transfer->get(Config::configFile($this->_indexByDateFile)))
        {
            return false;
        }

        $this->_manxDb->setProperty($this->_timeStampProperty, $this->_factory->getCurrentTime());
        return true;
    }

    private function waitForNonEmptyIndexByDateFile()
    {
        for ($retry = 0; $retry <= self::EMPTY_INDEX_MAX_RETRIES; ++$retry)
        {
            if (!$this->indexByDateFileIsEmpty())
            {
                return true;
            }

            if ($retry == self::EMPTY_INDEX_MAX_RETRIES)
            {
                $this->log(sprintf(
                    'IndexByDate.txt for site %s is still empty after %d retries; keeping existing local file.',
                    $this->_siteName, self::EMPTY_INDEX_MAX_RETRIES));
                return false;
            }

            $this->log(sprintf(
                'IndexByDate.txt for site %s is empty; retrying in %d seconds (%d of %d).',
                $this->_siteName, self::EMPTY_INDEX_RETRY_SECONDS,
                $retry + 1, self::EMPTY_INDEX_MAX_RETRIES));
            $this->sleepBeforeEmptyIndexRetry();
        }
        return true;
    }

    private function indexByDateFileIsEmpty()
    {
        $urlInfo = $this->_factory->createUrlInfo($this->_indexByDateUrl);
        $size = $urlInfo->size();
        return $size !== false && (string)$size == '0';
    }

    protected function sleepBeforeEmptyIndexRetry()
    {
        sleep(self::EMPTY_INDEX_RETRY_SECONDS);
    }

    private function log($text)
    {
        if (!is_null($this->_logger))
        {
            $this->_logger->log($text);
        }
    }

    public function parseIndexByDateFile()
    {
        $this->_manxDb->createTemporarySiteIndexByDate();
        try
        {
            $this->readIndexByDateBatches(function($rows) {
                $paths = [];
                foreach ($rows as $row)
                {
                    array_push($paths, $row['path']);
                }
                $this->_manxDb->addTemporarySiteIndexByDateRows(
                    $this->_siteName, $rows);
                $this->_manxDb->addTemporarySiteIndexDirectoryRows(
                    $this->_siteName, self::directoryPathsForRows($rows));
                $this->_manxDb->addSiteUnknownPaths($this->_siteName, $paths);
            });
            $this->_manxDb->removeSiteUnknownPathsMissingFromIndex(
                $this->_siteName);
            $this->_manxDb->removeSiteUnknownDirsMissingFromIndex(
                $this->_siteName);
        }
        finally
        {
            $this->_manxDb->dropTemporarySiteIndexByDate();
        }
    }

    public function loadIndexByDateTable()
    {
        $this->_manxDb->createTemporarySiteIndexByDate();
        $this->readIndexByDateBatches(function($rows) {
            $this->_manxDb->addTemporarySiteIndexByDateRows(
                $this->_siteName, $rows);
        });
    }

    public function dropIndexByDateTable()
    {
        $this->_manxDb->dropTemporarySiteIndexByDate();
    }

    private function readIndexByDateBatches($consumeRows)
    {
        $indexByDate = $this->_fileSystem->openFile(
            Config::configFile($this->_indexByDateFile), 'r');
        $rows = [];
        while (!$indexByDate->eof())
        {
            $row = self::parseIndexByDateLine(trim($indexByDate->getString()));
            if (!is_null($row))
            {
                array_push($rows, $row);
                if (count($rows) == self::INDEX_BATCH_SIZE)
                {
                    $consumeRows($rows);
                    $rows = [];
                }
            }
        }
        if (count($rows) > 0)
        {
            $consumeRows($rows);
        }
    }

    private static function parseIndexByDateLine($line)
    {
        if ($line == '')
        {
            return null;
        }
        if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}) [0-9]{2}:[0-9]{2}:[0-9]{2} (.+)$/',
            $line, $matches) == 1)
        {
            return self::indexRow($matches[2], $matches[1]);
        }
        $path = substr($line, 20);
        return ($path !== false && $path != '') ? self::indexRow($path, null) : null;
    }

    private static function indexRow($path, $indexDate)
    {
        $dirPath = pathinfo($path, PATHINFO_DIRNAME);
        if ($dirPath == '.')
        {
            $dirPath = '';
        }
        return [
            'path' => $path,
            'dir_path' => $dirPath,
            'filename' => self::decodedUrlBasename($path),
            'index_date' => $indexDate
        ];
    }

    private static function directoryPathsForRows($rows)
    {
        $dirs = [];
        foreach ($rows as $row)
        {
            foreach (self::directoryPaths($row['dir_path']) as $dir)
            {
                $dirs[$dir] = true;
            }
        }
        return array_keys($dirs);
    }

    private static function directoryPaths($dir)
    {
        $dirs = [];
        while ($dir != '')
        {
            $dirs[] = $dir;
            $dir = pathinfo($dir, PATHINFO_DIRNAME);
            if ($dir == '.')
            {
                $dir = '';
            }
        }
        return $dirs;
    }

    private static function decodedUrlBasename($path)
    {
        $lastSlash = strrpos($path, '/');
        $filename = ($lastSlash === false) ? $path : substr($path, $lastSlash + 1);
        return rawurldecode($filename);
    }

    private $_manxDb;
    private $_factory;
    private $_fileSystem;
    private $_timeStampProperty;
    private $_indexByDateUrl;
    private $_indexByDateFile;
    private $_baseUrl;
    private $_siteName;
    private $_logger;
}
