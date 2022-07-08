<?php

namespace Manx;

interface IFileSystem
{
    function openFile($path, $mode); // returns IFile
    function fileExists($path);
    function unlink($path);
    function rename($oldPath, $newPath);
}
