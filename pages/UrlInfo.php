<?php

require_once 'CurlApi.php';

class UrlInfo
{
    private $_api;
    private $_url;
    private $_session;

    public function __construct($url, ICurlApi $api = null)
    {
        $this->_url = $url;
        $this->_api = is_null($api) ? CurlApi::getInstance() : $api;
    }

    public function size()
    {
        return $this->getValueFromHeadResponse('content-length');
    }

    public function lastModified()
    {
        $lastModified = $this->getValueFromHeadResponse('last-modified');
        if (is_string($lastModified) && strlen($lastModified))
        {
            $lastModified = strtotime($lastModified);
        }
        return $lastModified;
    }

    private function getValueFromHeadResponse($header)
    {
        $result = $this->head();
        if (!$result)
        {
            $this->close();
            return false;
        }

        $httpStatus = $this->httpStatus();
        $this->close();

        $value = '';
        if ($httpStatus == 200)
        {
            $value = $this->getHeaderValue($result, $header);
        }
        else if ($httpStatus == 302)
        {
            $url = $this->getHeaderValue($result, 'location');
            if ($url)
            {
                $this->_url = $url;
                return $this->getValueFromHeadResponse($header);
            }
        }
        return $value;
    }

    public function httpStatus()
    {
        return $this->_api->getinfo($this->_session, CURLINFO_HTTP_CODE);
    }

    private function head()
    {
        $this->_session = $this->_api->init($this->_url);
        $this->_api->setopt($this->_session, CURLOPT_HEADER, 1);
        $this->_api->setopt($this->_session, CURLOPT_NOBODY, 1);
        $this->_api->setopt($this->_session, CURLOPT_RETURNTRANSFER, 1);
        $this->_api->setopt($this->_session, CURLOPT_FRESH_CONNECT, 1);
        $result = $this->_api->exec($this->_session);
        return $result;
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

    private function close()
    {
        $this->_api->close($this->_session);
    }
}
