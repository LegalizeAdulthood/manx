<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

interface ISearcher
{
    function renderCompanies($selected);
    function renderSearchResults(IFormatter $formatter, $company, $keywords, $online);
}
