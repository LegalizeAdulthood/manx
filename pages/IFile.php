<?php

interface IFile
{
    function eof();
    function getString();
    function getHandle();
    function close();
}

interface IFileFactory
{
    function openFile($path, $mode); // returns IFile
}
