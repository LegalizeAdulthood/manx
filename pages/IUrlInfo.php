<?php

interface IUrlInfo
{
    function size();
    function lastModified();
    function exists();
    function md5();
}
