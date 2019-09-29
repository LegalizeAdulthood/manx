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
        $this->writeCalled = false;
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
    public $getStringCalled, $getStringFakeResults;

    function getHandle()
    {
        $this->getHandleCalled = true;
        return $this->getHandleFakeResult;
    }
    public $getHandleCalled, $getHandleFakeResult;

    function close()
    {
        $this->closeCalled = true;
    }
    public $closeCalled;

    function write($data)
    {
        $this->writeCalled = true;
        $this->writeLastData = $data;
    }
    public $writeCalled, $writeLastData;
}

class FakeFileFactory implements IFileFactory
{
    public function __construct()
    {
        $this->openFileCalled = false;
        $this->openFileFakeResult = null;
    }

    function openFile($path, $mode)
    {
        $this->openFileCalled = true;
        return $this->$openFileFakeResult;
    }
    public $openFileCalled, $openFileFakeResult;
}
