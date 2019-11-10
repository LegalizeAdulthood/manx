<?php

require_once 'cron/IWhatsNewCleaner.php';

use Pimple\Container;

class WhatsNewProcessor
{
    public function __construct(Container $config)
    {
        $this->_cleaner = $config['whatsNewCleaner'];
        $this->_logger = $config['logger'];
    }

    private function log($text)
    {
        $this->_logger->log($text);
    }

    public function process(array $args)
    {
        if ($args[1] == 'help')
        {
            $this->log("existence:      remove non-existent unknown paths");
            $this->log("moved           update moved files");
            $this->log("index           fetch IndexByDate.txt");
            $this->log("unknown-copies  remove unknown paths with existing copy");
            $this->log("ingest          ingest copies from guessable unknown paths");
            $this->log("md5             compute MD5 hashes for copies");
        }
        else if ($args[1] == 'existence')
        {
            $this->_cleaner->removeNonExistentUnknownPaths();
        }
        else if ($args[1] == 'moved')
        {
            $this->_cleaner->updateMovedFiles();
        }
        else if ($args[1] == 'index')
        {
            $this->_cleaner->updateWhatsNewIndex();
        }
        else if ($args[1] == 'unknown-copies')
        {
            $this->_cleaner->removeUnknownPathsWithCopy();
        }
        else if ($args[1] == 'ingest')
        {
            $this->_cleaner->updateWhatsNewIndex();
            $this->_cleaner->ingest();
            $this->_cleaner->removeUnknownPathsWithCopy();
        }
        else if ($args[1] == 'md5')
        {
            $this->_cleaner->computeMissingMD5();
        }
    }
}
