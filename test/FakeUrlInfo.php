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
}
