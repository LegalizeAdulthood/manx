<?php

namespace Manx;

require_once 'vendor/autoload.php';

class UrlInfoFactory implements IUrlInfoFactory
{
    function createUrlInfo($url)
    {
        return new UrlInfo($url);
    }
}
