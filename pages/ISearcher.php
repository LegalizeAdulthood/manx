<?php

require_once 'vendor/autoload.php';

interface ISearcher
{
    function renderCompanies($selected);
    function renderSearchResults(Manx\IFormatter $formatter, $company, $keywords, $online);
}
