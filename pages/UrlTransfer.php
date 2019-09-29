<?php

require_once 'CurlApi.php';

interface IUrlTransfer
{
    function get($destination);
}

class UrlTransfer implements IUrlTransfer
{
    public function __construct($url, $curlApi = null, $fileFactory = null)
    {
        $this->_url = $url;
        $this->_api = is_null($curlApi) ? CurlApi::getInstance() : $curlApi;
        $this->_fileFactory = is_null($fileFactory) ? new FileFactory() : $fileFactory;
    }

    public function get($destination)
    {
        $session = $this->_api->init($this->_url);
        $tempDestination = $destination . ".tmp";
        $stream = $this->_fileFactory->openFile($tempDestination, 'w');
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

    private $_url;
    private $_api;
    private $_fileFactory;
}
