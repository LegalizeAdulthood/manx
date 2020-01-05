<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class TestWhatsNewProcessor extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_locker = $this->createMock(Manx\Cron\IExclusiveLock::class);
        $this->_cleaner = $this->createMock(Manx\Cron\IWhatsNewCleaner::class);
        $this->_logger = $this->createMock(Manx\Cron\ILogger::class);
        $config = new Container();
        $config['whatsNewCleaner'] = $this->_cleaner;
        $config['logger'] = $this->_logger;
        $config['locker'] = $this->_locker;
        $this->_processor = new Manx\Cron\WhatsNewProcessor($config);
    }

    public function testMD5()
    {
        $this->_locker->expects($this->once())->method('lock')->with('md5.lock');
        $this->_cleaner->expects($this->once())->method('computeMissingMD5');

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

    public function testHelp()
    {
        $this->_logger->expects($this->exactly(6))->method('log')->withConsecutive(
            [ "existence:      remove non-existent unknown paths" ],
            [ "moved           update moved files" ],
            [ "index           fetch IndexByDate.txt" ],
            [ "unknown-copies  remove unknown paths with existing copy" ],
            [ "ingest          ingest copies from guessable unknown paths" ],
            [ "md5             compute MD5 hashes for copies" ]
        );

        $this->_processor->process(['cleaner.php', 'help']);
    }

    private $_locker;
    private $_cleaner;
    private $_logger;
    private $_processor;
}
