<?php

require_once 'CurlApi.php';

interface IUrlTransfer
{
    function get($destination);
}

class UrlTransfer implements IUrlTransfer
{
    public function __construct($url, $curlApi = null, $fileSystem = null)
    {
        $this->_url = $url;
        $this->_curl = is_null($curlApi) ? CurlApi::getInstance() : $curlApi;
        $this->_fileSystem = is_null($fileSystem) ? new FileSystem() : $fileSystem;
    }

    public function get($destination)
    {
        $session = $this->_curl->init($this->_url);
        $tempDestination = $destination . ".tmp";
        $file = $this->_fileSystem->openFile($tempDestination, 'w');
        $this->_curl->setopt($session, CURLOPT_FILE, $file->getStream());
        $result = $this->_curl->exec($session);
        $httpStatus = $this->_curl->getinfo($session, CURLINFO_HTTP_CODE);
        $this->_curl->close($session);
        $file->close();
        if ($httpStatus != 200)
        {
            return false;
        }

        if ($this->_fileSystem->fileExists($destination))
        {
            $this->_fileSystem->unlink($destination);
        }
        $this->_fileSystem->rename($tempDestination, $destination);
        return true;
    }

    private $_url;
    private $_curl;
    private $_fileSystem;
}
