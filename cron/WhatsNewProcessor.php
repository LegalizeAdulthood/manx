<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class WhatsNewProcessor
{
    public function __construct(Container $config)
    {
        $this->_cleaner = $config['whatsNewCleaner'];
        $this->_logger = $config['logger'];
        $this->_locker = $config['locker'];
        $this->_dateTimeProvider = $config['dateTimeProvider'];
    }

    private function log($text)
    {
        $this->_logger->log($text);
    }

    public function process(array $args)
    {
        $command = $args[1];
        $startTime = $this->_dateTimeProvider->now();
        $this->log(sprintf("Begin %s", $command));
        try
        {
            if ($command == 'help')
            {
                $this->log("existence:      remove non-existent unknown paths");
                $this->log("moved           update moved files");
                $this->log("index           fetch IndexByDate.txt");
                $this->log("unknown-copies  remove unknown paths with existing copy");
                $this->log("ingest          ingest copies from guessable unknown paths");
                $this->log("md5             compute MD5 hashes for copies");
                $this->log("pdf-metadata    cache PDF metadata for unknown paths");
            }
            else if ($command == 'existence')
            {
                $this->lock($command);
                $this->_cleaner->removeNonExistentUnknownPaths();
            }
            else if ($command == 'moved')
            {
                $this->lock($command);
                $this->_cleaner->updateMovedFiles();
            }
            else if ($command == 'index')
            {
                $this->lock($command);
                $this->_cleaner->updateWhatsNewIndex();
                $this->_cleaner->removeUnknownPathsWithCopy();
                $this->_cleaner->updateIgnoredUnknownDirs();
            }
            else if ($command == 'unknown-copies')
            {
                $this->lock($command);
                $this->_cleaner->removeUnknownPathsWithCopy();
            }
            else if ($command == 'ingest')
            {
                $this->lock($command);
                $this->_cleaner->updateWhatsNewIndex();
                $this->_cleaner->ingest();
                $this->_cleaner->removeUnknownPathsWithCopy();
            }
            else if ($command == 'md5')
            {
                $this->lock($command);
                $this->_cleaner->computeMissingMD5();
            }
            else if ($command == 'pdf-metadata')
            {
                $this->lock($command);
                $this->_cleaner->cachePdfMetadata(
                    self::pdfMetadataTimeLimitSeconds($args));
            }
        }
        finally
        {
            $this->log(sprintf("End %s", $command));
            $this->log(sprintf("Total elapsed time: %.3f seconds",
                self::secondsBetween($startTime,
                    $this->_dateTimeProvider->now())));
        }
    }

    private static function secondsBetween($startTime, $endTime)
    {
        return max(0.0, self::seconds($endTime) - self::seconds($startTime));
    }

    private static function seconds($dateTime)
    {
        return intval($dateTime->format('U'))
            + intval($dateTime->format('u')) / 1000000.0;
    }

    private static function pdfMetadataTimeLimitSeconds(array $args)
    {
        $result = WhatsNewCleaner::DEFAULT_PDF_METADATA_TIME_LIMIT_SECONDS;
        for ($i = 2; $i < count($args); ++$i)
        {
            if ($args[$i] == '--time-limit-seconds' && $i + 1 < count($args))
            {
                $result = $args[$i + 1];
                ++$i;
            }
            else if (strpos($args[$i], '--time-limit-seconds=') === 0)
            {
                $result = substr($args[$i], strlen('--time-limit-seconds='));
            }
        }
        return preg_match('/^[0-9]+$/', (string)$result)
            ? intval($result)
            : WhatsNewCleaner::DEFAULT_PDF_METADATA_TIME_LIMIT_SECONDS;
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
    /** @var \Manx\IDateTimeProvider */
    private $_dateTimeProvider;
}
