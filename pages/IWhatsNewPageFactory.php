<?php

interface IWhatsNewPageFactory
{
    function createUrlInfo($url);
    function createUrlTransfer($url);
    function getCurrentTime();
}
