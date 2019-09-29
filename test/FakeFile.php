<?php

require_once 'pages/IFile.php';

class FakeFile implements IFile
{
    private $_line;

    public function __construct()
    {
        $this->_line = 0;
        $this->getStringCalled = false;
        $this->getStringFakeResults = array();
        $this->getHandleCalled = false;
        $this->getHandleFakeResult = null;
        $this->closeCalled = false;
    }

    function eof()
    {
        $this->eofCalled = true;
        return $this->_line >= count($this->getStringFakeResults);
    }
    public $eofCalled;

    function getString()
    {
        $this->getStringCalled = true;
        if ($this->_line < count($this->getStringFakeResults))
        {
            return $this->getStringFakeResults[$this->_line++];
        }
    }

    public $getHandleCalled, $getHandleFakeResult
    function getHandle()
    {
        $this->getHandleCalled = true;
        return $this->getHandleFakeResult;
    }

    public $closeCalled;
    function close()
    {
        $this->closeCalled = true;
    }

    public $getStringCalled, $getStringFakeResults;
}

class FakeFileFactory implements IFileFactory
{
    public function __construct()
    {
        $this->openFileCalled = false;
    }

    public $openFileCalled, $openFileFakeResult;
    function openFile($path, $mode)
    {
        return $this->$openFileFakeResult;
    }
}