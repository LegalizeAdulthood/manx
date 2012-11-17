<?php

interface IBitSaversPageFactory
{
    function openFile($path, $mode);
    function createUrlInfo($url);
    function createUrlTransfer($url);
    function getCurrentTime();
}
