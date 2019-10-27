<?php

use Pimple\Container;

require_once 'pages/Config.php';
require_once 'pages/WhatsNewIndex.php';
require_once 'test/DatabaseTester.php';

class TestWhatsNewIndex extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;

    /** @var IManxDatabase */
    private $_db;
    /** @var IManx */
    private $_manx;
    /** @var IFileSystem */
    private $_fileSystem;
    /** @var IWhatsNewPageFactory */
    private $_factory;
    /** @var IUrlInfo */
    private $_info;
    /** @var IUrlTransfer */
    private $_transfer;
    /** @var BitSaversPageTester */
    private $_page;
    /** @var FakeFile */
    private $_file;
    private $_indexUrl;
    private $_indexFile;
    private $_property;
    /** @var IUrlInfo */
    private $_urlInfo;

    protected function setUp()
    {
        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->method('getDatabase')->willReturn($this->_db);
        $this->_fileSystem = $this->createMock(IFileSystem::class);
        $this->_factory = $this->createMock(IWhatsNewPageFactory::class);
        $this->_transfer = $this->createMock(IUrlTransfer::class);
        $this->_urlInfo = $this->createMock(IUrlInfo::class);
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
        $this->_whatsNew = new WhatsNewIndex($config);
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
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($this->_indexUrl)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('lastModified')->willReturn('20');

        $result = $this->_whatsNew->needIndexByDateFile();

        $this->assertTrue($result);
    }

    public function testIndexNotNeededWithOldLastModified()
    {
        $this->_db->expects($this->once())->method('getProperty')->with($this->_property)->willReturn('20');
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($this->_indexUrl)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('lastModified')->willReturn('10');

        $result = $this->_whatsNew->needIndexByDateFile();

        $this->assertFalse($result);
    }

    public function testIndexNeededWithoutLastModified()
    {
        $this->_db->expects($this->once())->method('getProperty')->with($this->_property)->willReturn('20');
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($this->_indexUrl)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('lastModified')->willReturn(false);
        $this->_factory->expects($this->once())->method('getCurrentTime')->willReturn('30');

        $result = $this->_whatsNew->needIndexByDateFile();

        $this->assertTrue($result);
    }

    public function testGetIndex()
    {
        $this->_factory->expects($this->once())->method('createUrlTransfer')->with($this->_indexUrl)->willReturn($this->_transfer);
        $this->_transfer->expects($this->once())->method('get')->with(PRIVATE_DIR . $this->_indexFile);
        $now = '50';
        $this->_factory->expects($this->once())->method('getCurrentTime')->willReturn($now);
        $this->_db->expects($this->once())->method('setProperty')->with($this->_property, $now);

        $this->_whatsNew->getIndexByDateFile();
    }

    public function testParseIndex()
    {
        $file = $this->createMock(IFile::class);
        $this->_fileSystem->expects($this->once())->method('openFile')->with(PRIVATE_DIR . $this->_indexFile, 'r')->willReturn($file);
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
        $file = $this->createMock(IFile::class);
        $this->_fileSystem->expects($this->once())->method('openFile')->with(PRIVATE_DIR . $this->_indexFile, 'r')->willReturn($file);
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
}
