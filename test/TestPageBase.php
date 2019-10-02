<?php

require_once 'pages/PageBase.php';
require_once 'test/FakeManxDatabase.php';

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

    private function startOutputCapture()
    {
        ob_start();
    }

    private function finishOutputCapture()
    {
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    public function testRenderLoginLink()
    {
        $this->createInstance();
        $this->startOutputCapture();
        $this->_page->renderLoginLink(array('PHP_SELF' => '/manx/about.php',
            'SCRIPT_NAME' => '/manx/about.php',
            'SERVER_NAME' => 'localhost'));
        $output = $this->finishOutputCapture();
        $this->assertEquals('<a href="https://localhost/manx/login.php?redirect=%2Fmanx%2Fabout.php">Login</a>', $output);
    }
}
