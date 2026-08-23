<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class WhatsNewProcessorTest extends PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        $this->_locker = $this->createMock(Manx\Cron\IExclusiveLock::class);
        $this->_cleaner = $this->createMock(Manx\Cron\IWhatsNewCleaner::class);
        $this->_logger = $this->createMock(Manx\Cron\ILogger::class);
        $this->_dateTimeProvider =
            $this->createMock(Manx\IDateTimeProvider::class);
        $this->_dateTimeProvider->method('now')->willReturnOnConsecutiveCalls(
            self::dateTime('2026-08-23 12:00:00.000000'),
            self::dateTime('2026-08-23 12:00:03.500000'));
        $config = new Container();
        $config['whatsNewCleaner'] = $this->_cleaner;
        $config['logger'] = $this->_logger;
        $config['locker'] = $this->_locker;
        $config['dateTimeProvider'] = $this->_dateTimeProvider;
        $this->_processor = new Manx\Cron\WhatsNewProcessor($config);
    }

    private static function dateTime($time)
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s.u', $time,
            new \DateTimeZone('UTC'));
    }

    public function testMD5()
    {
        $this->_locker->expects($this->once())->method('lock')->with('md5.lock');
        $this->_cleaner->expects($this->once())->method('computeMissingMD5');
        $this->_logger->expects($this->exactly(3))->method('log')
            ->withConsecutive(
                [ 'Begin md5' ],
                [ 'End md5' ],
                [ 'Total elapsed time: 3.500 seconds' ]);

        $this->_processor->process(['cleaner.php', 'md5']);
    }

    public function testExistence()
    {
        $this->_locker->expects($this->once())->method('lock')->with('existence.lock');
        $this->_cleaner->expects($this->once())->method('removeNonExistentUnknownPaths');

        $this->_processor->process(['cleaner.php', 'existence']);
    }

    public function testMoved()
    {
        $this->_locker->expects($this->once())->method('lock')->with('moved.lock');
        $this->_cleaner->expects($this->once())->method('updateMovedFiles');

        $this->_processor->process(['cleaner.php', 'moved']);
    }

    public function testIndex()
    {
        $this->_locker->expects($this->once())->method('lock')->with('index.lock');
        $this->_cleaner->expects($this->once())->method('updateWhatsNewIndex');
        $this->_cleaner->expects($this->once())->method('removeUnknownPathsWithCopy');
        $this->_cleaner->expects($this->once())->method('updateIgnoredUnknownDirs');

        $this->_processor->process(['cleaner.php', 'index']);
    }


    public function testUnknownCopies()
    {
        $this->_locker->expects($this->once())->method('lock')->with('unknown-copies.lock');
        $this->_cleaner->expects($this->once())->method('removeUnknownPathsWithCopy');

        $this->_processor->process(['cleaner.php', 'unknown-copies']);
    }

    public function testIngest()
    {
        $this->_locker->expects($this->once())->method('lock')->with('ingest.lock');
        $this->_cleaner->expects($this->once())->method('updateWhatsNewIndex');
        $this->_cleaner->expects($this->once())->method('ingest');
        $this->_cleaner->expects($this->once())->method('removeUnknownPathsWithCopy');

        $this->_processor->process(['cleaner.php', 'ingest']);
    }

    public function testPdfMetadataDefaultTimeLimit()
    {
        $this->_locker->expects($this->once())->method('lock')
            ->with('pdf-metadata.lock');
        $this->_cleaner->expects($this->once())
            ->method('cachePdfMetadata')
            ->with(Manx\Cron\WhatsNewCleaner::DEFAULT_PDF_METADATA_TIME_LIMIT_SECONDS);

        $this->_processor->process(['cleaner.php', 'pdf-metadata']);
    }

    public function testPdfMetadataTimeLimitOption()
    {
        $this->_locker->expects($this->once())->method('lock')
            ->with('pdf-metadata.lock');
        $this->_cleaner->expects($this->once())
            ->method('cachePdfMetadata')
            ->with(60);

        $this->_processor->process(['cleaner.php', 'pdf-metadata',
            '--time-limit-seconds', '60']);
    }

    public function testHelp()
    {
        $this->_logger->expects($this->exactly(10))->method('log')->withConsecutive(
            [ "Begin help" ],
            [ "existence:      remove non-existent unknown paths" ],
            [ "moved           update moved files" ],
            [ "index           fetch IndexByDate.txt" ],
            [ "unknown-copies  remove unknown paths with existing copy" ],
            [ "ingest          ingest copies from guessable unknown paths" ],
            [ "md5             compute MD5 hashes for copies" ],
            [ "pdf-metadata    cache PDF metadata for unknown paths" ],
            [ "End help" ],
            [ "Total elapsed time: 3.500 seconds" ]
        );

        $this->_processor->process(['cleaner.php', 'help']);
    }

    private $_locker;
    private $_cleaner;
    private $_logger;
    private $_dateTimeProvider;
    private $_processor;
}
