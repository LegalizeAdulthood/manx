<?php

require_once 'cron/IWhatsNewCleaner.php';

use Pimple\Container;

class WhatsNewProcessor
{
    public function __construct(Container $config)
    {
        $this->_cleaner = $config['whatsNewCleaner'];
    }

    public function process(array $args)
    {
        if ($args[1] == 'existence')
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
    }
}
