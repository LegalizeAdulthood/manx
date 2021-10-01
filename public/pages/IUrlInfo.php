<?php

namespace Manx;

interface IUrlInfo
{
    function size();
    function lastModified();
    function exists();
    function md5();
    function url();
}
