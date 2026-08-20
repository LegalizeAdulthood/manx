<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

class UrlInfo implements IUrlInfo
{
    private $_url;
    private $_client;
    private $_response;

    public function __construct($url, \GuzzleHttp\Client $client = null)
    {
        $this->_url = $url;
        $options = array(
            'allow_redirects' => \GuzzleHttp\RedirectMiddleware::$defaultSettings,
            'http_errors' => false
        );
        $options['allow_redirects']['track_redirects'] = true;
        $this->_client = is_null($client) ? new \GuzzleHttp\Client($options) : $client;
        $this->_response = null;
    }

    public function url()
    {
        return $this->_url;
    }

    public function md5()
    {
        return md5_file($this->_url);
    }

    public function size()
    {
        $this->head();
        return $this->getValueFromHeadResponse('Content-Length');
    }

    public function lastModified()
    {
        $this->head();
        $value = $this->getValueFromHeadResponse('Last-Modified');
        return $value ? strtotime($value) : $value;
    }

    private function getValueFromHeadResponse($header)
    {

        $httpStatus = $this->_response->getStatusCode();
        $value = false;
        if ($httpStatus == 200)
        {
            $hdr = $this->_response->getHeader($header);
            if (is_array($hdr) && count($hdr) > 0)
            {
                $value = $hdr[0];
            }
        }
        return $value;
    }

    public function exists()
    {
        $this->head();
        return $this->_response->getStatusCode() == 200;
    }

    private function head()
    {
        if (is_null($this->_response))
        {
            $this->_response = $this->_client->head($this->_url, array('http_errors' => false));
            $history = $this->_response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER);
            if (is_array($history) && count($history) > 0)
            {
                $this->_url = end($history);
            }
        }
    }

    private function getHeaderValue($headers, $name)
    {
        foreach (explode("\n", str_replace("\r", '', $headers)) as $line)
        {
            if (strpos($line, ':') > 0)
            {
                list($header, $value) = explode(':', $line, 2);
                if (strtolower($header) == $name)
                {
                    return trim($value);
                }
            }
        }
        return false;
    }
}
