<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

class File implements IFile
{
    public function __construct($path, $mode)
    {
        $this->_file = fopen($path, $mode);
    }

    public function __destruct()
    {
        if (!is_null($this->_file))
        {
            fclose($this->_file);
        }
    }

    public function eof()
    {
        return feof($this->_file);
    }

    public function getString()
    {
        return fgets($this->_file);
    }

    public function getHandle()
    {
        return $this->_file;
    }

    public function close()
    {
        fclose($this->_file);
        $this->_file = null;
    }

    public function write($data)
    {
        fwrite($this->_file, $data);
    }

    public function getStream()
    {
        return $this->_file;
    }

    private $_file;
}
