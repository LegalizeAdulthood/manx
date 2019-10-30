<?php

interface IUrlMetaData
{
    function determineData($url);
    function determineIngestData($siteId, $companyId, $url);
}
