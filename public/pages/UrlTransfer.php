<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

class UrlTransfer implements IUrlTransfer
{
    public function __construct($url, \GuzzleHttp\ClientInterface $client = null, $fileSystem = null)
    {
        $this->_url = $url;
        $this->_client = is_null($client) ? new \GuzzleHttp\Client(array('http_errors' => false)) : $client;
        $this->_fileSystem = is_null($fileSystem) ? new FileSystem() : $fileSystem;
    }

    public function get($destination)
    {
        $tempDestination = $destination . ".tmp";
        $file = $this->_fileSystem->openFile($tempDestination, 'w');
        $response = null;
        try
        {
            $response = $this->_client->request('GET', $this->_url,
                array('sink' => $file->getStream(), 'http_errors' => false));
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e)
        {
        }
        $file->close();
        if (is_null($response) || $response->getStatusCode() != 200)
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
    private $_client;
    private $_fileSystem;
}
