<?php

require_once 'IFile.php';

class File implements IFile
{
    public function __construct($path, $mode)
    {
        $this->_file = fopen($path, $mode);
    }

    public function __destruct()
    {
        fclose($this->_file);
    }

    public function eof()
    {
        return feof($this->_file);
    }

    public function getString()
    {
        return fgets($this->_file);
    }

    private $_file;
}
