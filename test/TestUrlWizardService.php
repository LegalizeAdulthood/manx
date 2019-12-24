<?php

require_once 'vendor/autoload.php';

require_once 'test/DatabaseTester.php';
require_once 'pages/UrlWizardService.php';

use Pimple\Container;

class UrlWizardServiceTester extends UrlWizardService
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

class TestUrlWizardService extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;
    private $_db;
    private $_manx;
    private $_meta;

    protected function setUp()
    {
        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->expects($this->once())->method('getDatabase')->willReturn($this->_db);
        $user = $this->createMock(Manx\IUser::class);
        $user->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->_manx->expects($this->once())->method('getUserFromSession')->willReturn($user);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->_meta = $this->createMock(Manx\IUrlMetaData::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['urlMetaData'] = $this->_meta;
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
}
