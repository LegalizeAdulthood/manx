<?php

require_once 'ICurlApi.php';

class CurlApi implements ICurlApi
{
    public static function getInstance()
    {
        return new CurlApi();
    }

    private function __construct()
    {
    }

    public function init($url)
    {
        return curl_init($url);
    }

    public function setopt($session, $opt, $value)
    {
        return curl_setopt($session, $opt, $value);
    }

    public function exec($session)
    {
        return curl_exec($session);
    }

    public function close($session)
    {
        curl_close($session);
    }

    public function getinfo($session, $opt)
    {
        return curl_getinfo($session, $opt);
    }
}
