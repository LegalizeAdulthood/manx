<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class WhatsNewIndexTest extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;

    /** @var Manx\IManxDatabase */
    private $_db;
    /** @var Manx\IManx */
    private $_manx;
    /** @var Manx\IFileSystem */
    private $_fileSystem;
    /** @var Manx\IWhatsNewPageFactory */
    private $_factory;
    /** @var Manx\IUrlTransfer */
    private $_transfer;
    private $_indexUrl;
    private $_indexFile;
    private $_property;
    /** @var Manx\IUrlInfo */
    private $_urlInfo;
    /** @var Manx\WhatsNewIndex */
    private $_whatsNew;

    protected function setUp(): void
    {
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_manx->method('getDatabase')->willReturn($this->_db);
        $this->_fileSystem = $this->createMock(Manx\IFileSystem::class);
        $this->_factory = $this->createMock(Manx\IWhatsNewPageFactory::class);
        $this->_transfer = $this->createMock(Manx\IUrlTransfer::class);
        $this->_urlInfo = $this->createMock(Manx\IUrlInfo::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['fileSystem'] = $this->_fileSystem;
        $config['whatsNewPageFactory'] = $this->_factory;
        $this->_property = 'timestamp';
        $config['timeStampProperty'] = $this->_property;
        $this->_indexUrl = 'http://bitsavers.trailing-edge.com/pdf/IndexByDate.txt';
        $config['indexByDateUrl'] = $this->_indexUrl;
        $this->_indexFile = 'IndexByDate.txt';
        $config['indexByDateFile'] = $this->_indexFile;
        $config['baseUrl'] = 'http://www.bitsavers.org/pdf';
        $config['siteName'] = 'bitsavers';
        $this->_config = $config;
        $this->_whatsNew = new Manx\WhatsNewIndex($config);
    }

    public function testIndexNeededWithoutTimeStampProperty()
    {
        $this->_db->expects($this->once())->method('getProperty')->with($this->_property)->willReturn(false);

        $result = $this->_whatsNew->needIndexByDateFile();

        $this->assertTrue($result);
    }

    public function testIndexNeededWithNewLastModified()
    {
        $this->_db->expects($this->once())->method('getProperty')->with($this->_property)->willReturn('10');
        $this->_fileSystem->expects($this->once())->method('fileExists')
            ->with(\Manx\Config::configFile($this->_indexFile))
            ->willReturn(true);
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($this->_indexUrl)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('lastModified')->willReturn('20');

        $result = $this->_whatsNew->needIndexByDateFile();

        $this->assertTrue($result);
    }

    public function testIndexNeededWithoutLocalFile()
    {
        $this->_db->expects($this->once())->method('getProperty')->with($this->_property)->willReturn('20');
        $this->_fileSystem->expects($this->once())->method('fileExists')
            ->with(\Manx\Config::configFile($this->_indexFile))
            ->willReturn(false);
        $this->_factory->expects($this->never())->method('createUrlInfo');

        $result = $this->_whatsNew->needIndexByDateFile();

        $this->assertTrue($result);
    }

    public function testIndexNotNeededWithOldLastModified()
    {
        $this->_db->expects($this->once())->method('getProperty')->with($this->_property)->willReturn('20');
        $this->_fileSystem->expects($this->once())->method('fileExists')
            ->with(\Manx\Config::configFile($this->_indexFile))
            ->willReturn(true);
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($this->_indexUrl)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('lastModified')->willReturn('10');

        $result = $this->_whatsNew->needIndexByDateFile();

        $this->assertFalse($result);
    }

    public function testIndexNeededWithoutLastModified()
    {
        $this->_db->expects($this->once())->method('getProperty')->with($this->_property)->willReturn('20');
        $this->_fileSystem->expects($this->once())->method('fileExists')
            ->with(\Manx\Config::configFile($this->_indexFile))
            ->willReturn(true);
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($this->_indexUrl)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('lastModified')->willReturn(false);
        $this->_factory->expects($this->once())->method('getCurrentTime')->willReturn('30');

        $result = $this->_whatsNew->needIndexByDateFile();

        $this->assertTrue($result);
    }

    public function testGetIndex()
    {
        $this->_factory->expects($this->once())->method('createUrlTransfer')->with($this->_indexUrl)->willReturn($this->_transfer);
        $this->_transfer->expects($this->once())->method('get')->with(\Manx\Config::configFile($this->_indexFile));
        $now = '50';
        $this->_factory->expects($this->once())->method('getCurrentTime')->willReturn($now);
        $this->_db->expects($this->once())->method('setProperty')->with($this->_property, $now);

        $this->_whatsNew->getIndexByDateFile();
    }

    public function testParseIndex()
    {
        $file = $this->createMock(Manx\IFile::class);
        $this->_fileSystem->expects($this->once())->method('openFile')->with(\Manx\Config::configFile($this->_indexFile), 'r')->willReturn($file);
        $file->expects($this->exactly(3))->method('eof')->willReturn(false, false, true);
        $file->expects($this->exactly(2))->method('getString')->willReturn(
            '2019-10-27 03:40:42 ibm/4381/fe/SY24-4024-2_A08_4381_Processor_Group_3_Console_Functions_and_Messages_Sep1985.pdf',
            '2019-10-27 01:24:00 ibm/370/VM_SP/Release_5_Dec86/SC24-5237-3_VM_SP_Release_5_Installation_Guide_Dec1986.pdf');
        $this->_db->expects($this->once())->method('addSiteUnknownPaths')->
            with($this->_config['siteName'],
                ['ibm/4381/fe/SY24-4024-2_A08_4381_Processor_Group_3_Console_Functions_and_Messages_Sep1985.pdf',
                'ibm/370/VM_SP/Release_5_Dec86/SC24-5237-3_VM_SP_Release_5_Installation_Guide_Dec1986.pdf']);

        $this->_whatsNew->parseIndexByDateFile();
    }

    public function testParseIndexSkipsBlankLines()
    {
        $file = $this->createMock(Manx\IFile::class);
        $this->_fileSystem->expects($this->once())->method('openFile')->with(\Manx\Config::configFile($this->_indexFile), 'r')->willReturn($file);
        $file->expects($this->exactly(4))->method('eof')->willReturn(false, false, false, true);
        $file->expects($this->exactly(3))->method('getString')->willReturn(
            '2019-10-27 03:40:42 ibm/4381/fe/SY24-4024-2_A08_4381_Processor_Group_3_Console_Functions_and_Messages_Sep1985.pdf',
            '2019-10-27 01:24:00 ibm/370/VM_SP/Release_5_Dec86/SC24-5237-3_VM_SP_Release_5_Installation_Guide_Dec1986.pdf',
            '');
        $this->_db->expects($this->once())->method('addSiteUnknownPaths')->
            with($this->_config['siteName'],
                ['ibm/4381/fe/SY24-4024-2_A08_4381_Processor_Group_3_Console_Functions_and_Messages_Sep1985.pdf',
                'ibm/370/VM_SP/Release_5_Dec86/SC24-5237-3_VM_SP_Release_5_Installation_Guide_Dec1986.pdf']);

        $this->_whatsNew->parseIndexByDateFile();
    }

    public function testParseIndexBatchesRows()
    {
        $lines = self::indexLines(501);
        $file = $this->createMock(Manx\IFile::class);
        $this->_fileSystem->expects($this->once())->method('openFile')
            ->with(\Manx\Config::configFile($this->_indexFile), 'r')
            ->willReturn($file);
        $file->expects($this->exactly(502))->method('eof')
            ->willReturn(...self::eofResults(count($lines)));
        $file->expects($this->exactly(501))->method('getString')
            ->willReturn(...$lines);
        $calls = [];
        $this->_db->expects($this->exactly(2))->method('addSiteUnknownPaths')
            ->willReturnCallback(
                function($siteName, $paths) use (&$calls) {
                    array_push($calls, [$siteName, $paths]);
                });

        $this->_whatsNew->parseIndexByDateFile();

        $this->assertCount(500, $calls[0][1]);
        $this->assertCount(1, $calls[1][1]);
        $this->assertSame($this->_config['siteName'], $calls[0][0]);
        $this->assertSame('dec/pdp11/file000.pdf', $calls[0][1][0]);
        $this->assertSame('dec/pdp11/file500.pdf', $calls[1][1][0]);
    }

    private static function eofResults($lineCount)
    {
        $eof = array_fill(0, $lineCount, false);
        array_push($eof, true);
        return $eof;
    }

    private static function indexLines($count)
    {
        $lines = [];
        for ($i = 0; $i < $count; ++$i)
        {
            array_push($lines, sprintf(
                '2019-10-27 03:40:42 dec/pdp11/file%03d.pdf', $i));
        }
        return $lines;
    }

    public function testLoadIndexByDateTable()
    {
        $file = $this->createMock(Manx\IFile::class);
        $this->_fileSystem->expects($this->once())->method('openFile')->with(\Manx\Config::configFile($this->_indexFile), 'r')->willReturn($file);
        $file->expects($this->exactly(3))->method('eof')->willReturn(false, false, true);
        $file->expects($this->exactly(2))->method('getString')->willReturn(
            '2019-10-27 03:40:42 ibm/4381/fe/SY24-4024-2_A08_4381_Processor_Group_3_Console_Functions_and_Messages_Sep1985.pdf',
            '2019-10-27 01:24:00 ibm/370/VM_SP/Release_5_Dec86/SC24-5237-3_VM_SP_Release_5_Installation_Guide_Dec1986.pdf');
        $this->_db->expects($this->once())->method('createTemporarySiteIndexByDate');
        $this->_db->expects($this->once())->method('addTemporarySiteIndexByDateRows')
            ->with($this->_config['siteName'], [
                [
                    'path' => 'ibm/4381/fe/SY24-4024-2_A08_4381_Processor_Group_3_Console_Functions_and_Messages_Sep1985.pdf',
                    'dir_path' => 'ibm/4381/fe',
                    'filename' => 'SY24-4024-2_A08_4381_Processor_Group_3_Console_Functions_and_Messages_Sep1985.pdf',
                    'index_date' => '2019-10-27'
                ],
                [
                    'path' => 'ibm/370/VM_SP/Release_5_Dec86/SC24-5237-3_VM_SP_Release_5_Installation_Guide_Dec1986.pdf',
                    'dir_path' => 'ibm/370/VM_SP/Release_5_Dec86',
                    'filename' => 'SC24-5237-3_VM_SP_Release_5_Installation_Guide_Dec1986.pdf',
                    'index_date' => '2019-10-27'
                ]
            ]);

        $this->_whatsNew->loadIndexByDateTable();
    }

    public function testLoadIndexByDateTableDecodesFilename()
    {
        $file = $this->createMock(Manx\IFile::class);
        $this->_fileSystem->expects($this->once())->method('openFile')->with(\Manx\Config::configFile($this->_indexFile), 'r')->willReturn($file);
        $file->expects($this->exactly(2))->method('eof')->willReturn(false, true);
        $file->expects($this->once())->method('getString')->willReturn(
            '2019-10-27 03:40:42 dec/pdp11/file%20%231.pdf');
        $this->_db->expects($this->once())->method('createTemporarySiteIndexByDate');
        $this->_db->expects($this->once())->method('addTemporarySiteIndexByDateRows')
            ->with($this->_config['siteName'], [
                [
                    'path' => 'dec/pdp11/file%20%231.pdf',
                    'dir_path' => 'dec/pdp11',
                    'filename' => 'file #1.pdf',
                    'index_date' => '2019-10-27'
                ]
            ]);

        $this->_whatsNew->loadIndexByDateTable();
    }

    public function testDropIndexByDateTable()
    {
        $this->_db->expects($this->once())->method('dropTemporarySiteIndexByDate');

        $this->_whatsNew->dropIndexByDateTable();
    }
}
