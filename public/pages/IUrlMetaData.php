<?php

namespace Manx;

interface IUrlMetaData
{
    function determineData($url);
    function determineIngestData($siteId, $companyId, $url, $partRegex = '');
    function getCopyMD5($url);
}
