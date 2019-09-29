<?php

require_once 'CurlApi.php';

interface IUrlTransfer
{
    function get($destination);
}

class UrlTransfer implements IUrlTransfer
{
    private $_url;
    private $_api;

    public function __construct($url, $curlApi = null, $fileApi = null)
    {
        $this->_url = $url;
        $this->_api = is_null($curlApi) ? CurlApi::getInstance() : $curlApi;
        $this->_fileApi = is_null($fileApi) ? new FileFactory() : $fileApi;
    }

    public function get($destination)
    {
        $session = $this->_api->init($this->_url);
        $tempDestination = $destination . ".tmp";
        $stream = $this->_fileApi->openFile($tempDestination, 'w');
        $result = $this->_api->exec($session);
        $httpStatus = $this->_api->getinfo($session, CURLINFO_HTTP_CODE);
        $this->_api->close($session);
        $stream->close();
        if ($httpStatus == 200)
        {
            if (file_exists($destination))
            {
                unlink($destination);
            }
            rename($tempDestination, $destination);
            return true;
        }
        unlink($tempDestination);
        return false;
    }
}
