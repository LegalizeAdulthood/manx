<?php

require_once 'pages/UrlInfo.php';

class FakeUrlInfo implements IUrlInfo
{
    function size()
    {
        throw new BadMethodCallException();
    }

    function lastModified()
    {
        $this->lastModifiedCalled = true;
        return $this->lastModifiedFakeResult;
    }
    public $lastModifiedCalled, $lastModifiedFakeResult;

    function exists()
    {
        $this->existsCalled = true;
        return $this->existsFakeResult;
    }
    public $existsCalled, $existsFakeResult;

    function md5()
    {
        $this->md5Called = true;
        return $this->md5FakeResult;
    }
    public $md5Called, $md5FakeResult;
}
