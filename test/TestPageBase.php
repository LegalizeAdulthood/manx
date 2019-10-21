<?php

require_once 'vendor/autoload.php';
require_once 'pages/PageBase.php';

use Pimple\Container;

class PageBaseTester extends PageBase
{
    protected function renderBodyContent()
    {
    }
}

class TestPageBase extends PHPUnit\Framework\TestCase
{
    private $_manx;
    /** @var PageBaseTester */
    private $_page;

    private function createInstance()
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_manx = $this->createMock(IManx::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $this->_page = new PageBaseTester($config);
    }

    public function testRenderLoginLink()
    {
        $this->createInstance();

        $this->_page->renderLoginLink(array('PHP_SELF' => '/manx/about.php',
            'SCRIPT_NAME' => '/manx/about.php',
            'SERVER_NAME' => 'localhost'));

        $this->expectOutputString('<a href="https://localhost/manx/login.php?redirect=%2Fmanx%2Fabout.php">Login</a>');
    }
}
