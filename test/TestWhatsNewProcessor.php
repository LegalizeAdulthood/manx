<?php

require_once 'cron/WhatsNewProcessor.php';

use Pimple\Container;

class TestWhatsNewProcessor extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_cleaner = $this->createMock(IWhatsNewCleaner::class);
        $config = new Container();
        $config['whatsNewCleaner'] = $this->_cleaner;
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
        $this->_cleaner->expects($this->once())->method('ingest');

        $this->_processor->process(['cleaner.php', 'ingest']);
    }

    private $_cleaner;
    private $_processor;
}
