<?php

require_once 'File.php';
require_once 'IWhatsNewPageFactory.php';
require_once 'IDateTimeProvider.php';
require_once 'UrlInfo.php';
require_once 'UrlTransfer.php';

class WhatsNewPageFactory implements IWhatsNewPageFactory
{
    public function __construct($fileSystem = nullptr)
    {
        $this->_fileSystem = is_null($fileSystem) ? new FileSystem() : $fileSystem;
    }

    function openFile($path, $mode)
    {
        return $this->_fileSystem->openFile($path, $mode);
    }

    function createUrlInfo($url)
    {
        return new UrlInfo($url);
    }

    function createUrlTransfer($url)
    {
        return new UrlTransfer($url);
    }

    function getCurrentTime()
    {
        date_default_timezone_set(TIME_ZONE);
        return time();
    }

    private $_fileSystem;
}
