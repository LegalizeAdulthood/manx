<?php

require_once 'IUrlInfoFactory.php';
require_once 'UrlInfo.php';

class UrlInfoFactory implements IUrlInfoFactory
{
    function createUrlInfo($url)
    {
        return new UrlInfo($url);
    }
}
