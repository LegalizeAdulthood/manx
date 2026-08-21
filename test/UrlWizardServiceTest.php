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
        $this->_meta->expects($this->once())->method('determineData')->willReturn(['valid' => false]);
        $vars = self::varsForUrlLookup($url);
        $this->_config['vars'] = $vars;
        $page = new UrlWizardServiceTester($this->_config);

        $page->processRequest();

        $expected = json_encode(array('valid' => false));
        $this->expectOutputString($expected);
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

    private static function varsForPdfMetadata($url)
    {
        return array(
            'method' => 'pdf-metadata',
            'url' => $url
        );
    }
}
