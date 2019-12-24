<?php

namespace Manx;

interface IFile
{
    function eof();
    function getString();
    function getHandle();
    function write($data);
    function close();
    function getStream();
}
