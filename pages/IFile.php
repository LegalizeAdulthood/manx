<?php

interface IFile
{
    function eof();
    function getString();
    function getHandle();
    function write(string $data);
    function close();
    function getStream();
}

interface IFileSystem
{
    function openFile(string $path, string $mode); // returns IFile
    function fileExists(string $path);
    function unlink(string $path);
    function rename(string $oldPath, string $newPath);
}
