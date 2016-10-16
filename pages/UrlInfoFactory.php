<?php

require_once 'IUrlInfoFactory.php';

class UrlInfoFactory implements IUrlInfoFactory
{
    function createUrlInfo($url)
    {
        return new UrlInfo($url);
    }
}
