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

    public function getHandle()
    {
        return $this->_file;
    }

    public function close()
    {
        fclose($this->_file);
    }

    public function write($data)
    {
        fwrite($this->_file, $data);
    }

    private $_file;
}

class FileSystem implements IFileSystem
{
    public function openFile($path, $mode)
    {
        return new File($path, $mode);
    }

    public function fileExists($path)
    {
        return file_exists($path);
    }

    public function unlink($path)
    {
        return unlink($path);
    }

    public function rename($oldPath, $newPath)
    {
        return rename($oldPath, $newPath);
    }
}
