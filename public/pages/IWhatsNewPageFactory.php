<?php

namespace Manx;

interface IWhatsNewPageFactory
{
    function createUrlInfo($url);
    function createUrlTransfer($url);
    function getCurrentTime();
}
