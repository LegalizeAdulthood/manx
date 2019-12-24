<?php

namespace Manx;

require_once 'vendor/autoload.php';

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
