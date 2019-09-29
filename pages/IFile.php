<?php

interface IFile
{
    function eof();
    function getString();
    function getHandle();
    function write($data);
    function close();
}

interface IFileSystem
{
    function openFile($path, $mode); // returns IFile
    function fileExists($path);
    function unlink($path);
    function rename($oldPath, $newPath);
}
