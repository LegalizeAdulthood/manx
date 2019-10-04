<?php

require_once 'pages/PageBase.php';

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
        $this->_page = new PageBaseTester($this->_manx);
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
