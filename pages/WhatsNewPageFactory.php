<?php

require_once 'vendor/autoload.php';

require_once 'IDateTimeProvider.php';

class WhatsNewPageFactory implements Manx\IWhatsNewPageFactory
{
    function createUrlInfo($url)
    {
        return new Manx\UrlInfo($url);
    }

    function createUrlTransfer($url)
    {
        return new Manx\UrlTransfer($url);
    }

    function getCurrentTime()
    {
        date_default_timezone_set(Manx\TIME_ZONE);
        return time();
    }
}
