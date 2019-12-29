<?php

namespace Manx;

require_once __DIR__ . '/../vendor/autoload.php';

class UrlInfoFactory implements IUrlInfoFactory
{
    function createUrlInfo($url)
    {
        return new UrlInfo($url);
    }
}
