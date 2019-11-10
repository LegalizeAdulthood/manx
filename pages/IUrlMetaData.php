<?php

interface IUrlMetaData
{
    function determineData($url);
    function determineIngestData($siteId, $companyId, $url);
    function getCopyMD5($url);
}
