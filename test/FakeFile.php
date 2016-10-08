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
}
