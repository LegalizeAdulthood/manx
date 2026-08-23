<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class UrlWizardServiceTester extends Manx\UrlWizardService
{
    public function renderBodyContent()
    {
        parent::renderBodyContent();
    }

    protected function redirect($target)
    {
        $this->redirectCalled = true;
        $this->redirectLastTarget = $target;
    }
    public $redirectCalled, $redirectLastTarget;

    public function postPage()
    {
        parent::postPage();
    }

    protected function header($field)
    {
        $this->headerCalled = true;
        $this->headerLastField = $field;
    }
    public $headerCalled, $headerLastField;
}

class UrlWizardServiceTest extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;
    private $_db;
    private $_manx;
    private $_meta;
    private $_pdfMetadata;

    protected function setUp(): void
    {
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_manx->expects($this->once())->method('getDatabase')->willReturn($this->_db);
        $user = $this->createMock(Manx\IUser::class);
        $user->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->_manx->expects($this->once())->method('getUserFromSession')->willReturn($user);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->_meta = $this->createMock(Manx\IUrlMetaData::class);
        $this->_pdfMetadata = $this->createMock(Manx\IPdfMetadata::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['urlMetaData'] = $this->_meta;
        $config['pdfMetadata'] = $this->_pdfMetadata;
        $this->_config = $config;
    }

    public function testUrlLookup()
    {
        $url = 'http://bitsavers.org/pdf/sandersAssociates/graphic7/Graphic_7_Monitor_Preliminary_Users_Guide_May_1979.pdf';
        $this->_meta->expects($this->once())->method('determineData')
            ->with($url)->willReturn(['valid' => false]);
        $vars = self::varsForUrlLookup($url);
        $this->_config['vars'] = $vars;
        $page = new UrlWizardServiceTester($this->_config);

        $page->processRequest();

        $expected = json_encode(array('valid' => false));
        $this->expectOutputString($expected);
    }

    public function testUrlLookupKeepsUrlWhenCopyBaseDiffers()
    {
        $url = 'http://example.com/manuals/acme/ABC-123_Guide_Jan1980.pdf';
        $site = self::databaseRowFromDictionary([
            'site_id' => '77',
            'name' => 'example',
            'url' => 'http://example.com/manuals/',
            'description' => 'Example Manuals',
            'copy_base' => 'http://cdn.example.net/files/',
            'low' => 'N',
            'live' => 'Y',
            'display_order' => '77'
        ]);
        $urlInfo = $this->createMock(Manx\IUrlInfo::class);
        $urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $urlInfoFactory = $this->createMock(Manx\IUrlInfoFactory::class);
        $urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with($url)->willReturn($urlInfo);
        $this->_db->expects($this->once())->method('getSites')->willReturn([$site]);
        $this->_db->expects($this->never())->method('getMirrors');
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('copyExistsForUrl')
            ->with($url)->willReturn(false);
        $this->_config['db'] = $this->_db;
        $this->_config['urlInfoFactory'] = $urlInfoFactory;
        $this->_config['urlMetaData'] = new Manx\UrlMetaData($this->_config);
        $this->_config['vars'] = self::varsForUrlLookup($url);
        $page = new UrlWizardServiceTester($this->_config);

        ob_start();
        $page->processRequest();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertEquals($url, $data['url']);
        $this->assertEquals($site['site_id'], $data['site']['site_id']);
        $this->assertEquals('Content-Type: application/json; charset=utf-8',
            $page->headerLastField);
    }

    public function testUrlLookupHttpUrlMatchesHttpsSite()
    {
        $url = 'http://bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf';
        $site = self::databaseRowFromDictionary([
            'site_id' => '3',
            'name' => 'bitsavers',
            'url' => 'https://bitsavers.org',
            'description' => '',
            'copy_base' => 'https://bitsavers.org/pdf/',
            'low' => 'N',
            'live' => 'Y',
            'display_order' => '1'
        ]);
        $urlInfo = $this->createMock(Manx\IUrlInfo::class);
        $urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $urlInfoFactory = $this->createMock(Manx\IUrlInfoFactory::class);
        $urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with($url)->willReturn($urlInfo);
        $this->_db->expects($this->once())->method('getSites')
            ->willReturn([$site]);
        $this->_db->expects($this->never())->method('getMirrors');
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteDirectory')
            ->with('bitsavers', 'univac', '')
            ->willReturn('-1');
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('copyExistsForUrl')
            ->with($url)->willReturn(false);
        $this->_config['db'] = $this->_db;
        $this->_config['urlInfoFactory'] = $urlInfoFactory;
        $this->_config['urlMetaData'] =
            new Manx\UrlMetaData($this->_config);
        $this->_config['vars'] = self::varsForUrlLookup($url);
        $page = new UrlWizardServiceTester($this->_config);

        ob_start();
        $page->processRequest();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertEquals($url, $data['url']);
        $this->assertEquals($site['site_id'], $data['site']['site_id']);
        $this->assertEquals('UE-637', $data['part']);
    }

    public function testPdfMetadata()
    {
        $url = 'http://bitsavers.org/pdf/foo.pdf';
        $metadata = array(
            'status' => 'ok',
            'title' => 'Title',
            'keywords' => 'keywords',
            'abstract' => 'Abstract',
            'copy_notes' => 'Notes',
            'copy_credits' => 'Credits'
        );
        $this->_pdfMetadata->expects($this->once())->method('metadataForUrl')
            ->with($url)
            ->willReturn($metadata);
        $this->_config['vars'] = self::varsForPdfMetadata($url);
        $page = new UrlWizardServiceTester($this->_config);

        $page->processRequest();

        $this->expectOutputString(json_encode($metadata));
    }

    public function testPdfMetadataCachesSiteUnknownMetadata()
    {
        $url = 'http://bitsavers.org/pdf/foo.pdf';
        $siteUnknownId = 54118;
        $metadata = array(
            'status' => Manx\PdfMetadata::STATUS_OK,
            'title' => 'Title',
            'keywords' => 'keywords',
            'abstract' => 'Abstract',
            'copy_notes' => 'Notes',
            'copy_credits' => 'Credits'
        );
        $this->_pdfMetadata->expects($this->once())->method('metadataForUrl')
            ->with($url)
            ->willReturn($metadata);
        $this->_db->expects($this->once())
            ->method('updateSiteUnknownPdfMetadata')
            ->with($siteUnknownId, 'Title', 'keywords', 'Abstract', 'Notes',
                'Credits', 'ok', '');
        $this->_config['vars'] = self::varsForPdfMetadata($url,
            $siteUnknownId);
        $page = new UrlWizardServiceTester($this->_config);

        $page->processRequest();

        $this->expectOutputString(json_encode($metadata));
    }

    public function testPdfMetadataCachesFailedStatus()
    {
        $url = 'http://bitsavers.org/pdf/foo.pdf';
        $siteUnknownId = 54118;
        $metadata = array(
            'status' => Manx\PdfMetadata::STATUS_TOO_LARGE
        );
        $this->_pdfMetadata->expects($this->once())->method('metadataForUrl')
            ->with($url)
            ->willReturn($metadata);
        $this->_db->expects($this->once())
            ->method('updateSiteUnknownPdfMetadata')
            ->with($siteUnknownId, '', '', '', '', '', 'error', 'too_large');
        $this->_config['vars'] = self::varsForPdfMetadata($url,
            $siteUnknownId);
        $page = new UrlWizardServiceTester($this->_config);

        $page->processRequest();

        $this->expectOutputString(json_encode($metadata));
    }

    public function testPubSearchIgnoresInvalidCompany()
    {
        $this->_db->expects($this->never())->method('searchForPublications');
        $this->_config['vars'] = self::varsForPubSearch('', 'terminal');
        $page = new UrlWizardServiceTester($this->_config);

        $page->processRequest();

        $this->expectOutputString('[]');
    }

    public function testPubSearchDoesNotCreatePdfMetadataService()
    {
        $this->_config['pdfMetadata'] = function($c) {
            throw new RuntimeException('PDF metadata should not be used.');
        };
        $this->_db->expects($this->any())->method('searchForPublications')
            ->willReturn([]);
        $this->_config['vars'] = self::varsForPubSearch(13, 'terminal');
        $page = new UrlWizardServiceTester($this->_config);

        $page->processRequest();

        $this->expectOutputString('[]');
    }

    private static function databaseRowFromDictionary(array $dict)
    {
        $result = array();
        $i = 0;
        foreach ($dict as $key => $value)
        {
            $result[$key] = $value;
            $result[$i] = $value;
            $i++;
        }
        return $result;
    }

    private static function varsForUrlLookup($url)
    {
        return array(
            'method' => 'url-lookup',
            'url' => $url
        );
    }

    private static function varsForPdfMetadata($url, $siteUnknownId = null)
    {
        $vars = array(
            'method' => 'pdf-metadata',
            'url' => $url
        );
        if (!is_null($siteUnknownId))
        {
            $vars['id'] = $siteUnknownId;
        }
        return $vars;
    }

    private static function varsForPubSearch($company, $keywords)
    {
        return array(
            'method' => 'pub-search',
            'company' => $company,
            'keywords' => $keywords
        );
    }
}
