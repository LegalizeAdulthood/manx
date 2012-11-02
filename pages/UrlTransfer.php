<?php

require_once 'CurlApi.php';

class UrlTransfer
{
    private $_url;
    private $_api;

    public function __construct($url, $curlApi = null)
    {
        $this->_url = $url;
        $this->_api = is_null($curlApi) ? CurlApi::getInstance() : $curlApi;
    }

    public function get($destination)
    {
        $session = $this->_api->init($this->_url);
        $tempDestination = $destination . ".tmp";
        $stream = fopen($tempDestination, 'w');
        $this->_api->setopt($session, CURLOPT_FILE, $stream);
        $result = $this->_api->exec($session);
        $httpStatus = $this->_api->getinfo($session, CURLINFO_HTTP_CODE);
        $this->_api->close($session);
        fclose($stream);
        if ($httpStatus == 200)
        {
            unlink($destination);
            rename($tempDestination, $destination);
            return true;
        }
        unlink($tempDestination);
        return false;
    }
}
