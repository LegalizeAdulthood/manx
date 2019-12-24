<?php

require_once 'vendor/autoload.php';

require_once 'UrlInfo.php';

class UrlInfoFactory implements Manx\IUrlInfoFactory
{
    function createUrlInfo($url)
    {
        return new UrlInfo($url);
    }
}
