<?php

require_once 'IFile.php';

class File implements IFile
{
    public function __construct(string $path, string $mode)
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

    public function write(string $data)
    {
        fwrite($this->_file, $data);
    }

    public function getStream()
    {
        return $this->_file;
    }

    private $_file;
}

class FileSystem implements IFileSystem
{
    public function openFile(string $path, string $mode)
    {
        return new File($path, $mode);
    }

    public function fileExists(string $path)
    {
        return file_exists($path);
    }

    public function unlink(string $path)
    {
        return unlink($path);
    }

    public function rename(string $oldPath, string $newPath)
    {
        return rename($oldPath, $newPath);
    }
}
