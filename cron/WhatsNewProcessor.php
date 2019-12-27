<?php

namespace Manx\Cron;

require_once 'vendor/autoload.php';

use Pimple\Container;

class WhatsNewProcessor
{
    public function __construct(Container $config)
    {
        $this->_cleaner = $config['whatsNewCleaner'];
        $this->_logger = $config['logger'];
        $this->_locker = $config['locker'];
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
            $this->lock($args[1]);
            $this->_cleaner->removeNonExistentUnknownPaths();
        }
        else if ($args[1] == 'moved')
        {
            $this->lock($args[1]);
            $this->_cleaner->updateMovedFiles();
        }
        else if ($args[1] == 'index')
        {
            $this->lock($args[1]);
            $this->_cleaner->updateWhatsNewIndex();
            $this->_cleaner->removeUnknownPathsWithCopy();
        }
        else if ($args[1] == 'unknown-copies')
        {
            $this->lock($args[1]);
            $this->_cleaner->removeUnknownPathsWithCopy();
        }
        else if ($args[1] == 'ingest')
        {
            $this->lock($args[1]);
            $this->_cleaner->updateWhatsNewIndex();
            $this->_cleaner->ingest();
            $this->_cleaner->removeUnknownPathsWithCopy();
        }
        else if ($args[1] == 'md5')
        {
            $this->lock($args[1]);
            $this->_cleaner->computeMissingMD5();
        }
    }

    private function lock($name)
    {
        $this->_lock = $this->_locker->lock($name . '.lock');
    }

    /** @var IWhatsNewCleaner */
    private $_cleaner;
    /** @var ILogger */
    private $_logger;
    /** @var IExclusiveLock */
    private $_locker;
}
