<?php

require_once 'cron/WhatsNewProcessor.php';
require_once 'cron/ILogger.php';

use Pimple\Container;

class TestWhatsNewProcessor extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_cleaner = $this->createMock(IWhatsNewCleaner::class);
        $this->_logger = $this->createMock(ILogger::class);
        $config = new Container();
        $config['whatsNewCleaner'] = $this->_cleaner;
        $config['logger'] = $this->_logger;
        $this->_processor = new WhatsNewProcessor($config);
    }

    public function testExistence()
    {
        $this->_cleaner->expects($this->once())->method('removeNonExistentUnknownPaths');

        $this->_processor->process(['cleaner.php', 'existence']);
    }

    public function testMoved()
    {
        $this->_cleaner->expects($this->once())->method('updateMovedFiles');

        $this->_processor->process(['cleaner.php', 'moved']);
    }

    public function testIndex()
    {
        $this->_cleaner->expects($this->once())->method('updateWhatsNewIndex');

        $this->_processor->process(['cleaner.php', 'index']);
    }


    public function testUnknownCopies()
    {
        $this->_cleaner->expects($this->once())->method('removeUnknownPathsWithCopy');

        $this->_processor->process(['cleaner.php', 'unknown-copies']);
    }

    public function testIngest()
    {
        $this->_cleaner->expects($this->once())->method('updateWhatsNewIndex');
        $this->_cleaner->expects($this->once())->method('ingest');
        $this->_cleaner->expects($this->once())->method('removeUnknownPathsWithCopy');

        $this->_processor->process(['cleaner.php', 'ingest']);
    }

    public function testHelp()
    {
        $this->_logger->expects($this->exactly(5))->method('log')->withConsecutive(
            [ "existence:      remove non-existent unknown paths" ],
            [ "moved           update moved files" ],
            [ "index           fetch IndexByDate.txt" ],
            [ "unknown-copies  remove unknown paths with existing copy" ],
            [ "ingest          ingest copies from guessable unknown paths" ]
        );

        $this->_processor->process(['cleaner.php', 'help']);
    }

    private $_cleaner;
    private $_logger;
    private $_processor;
}
