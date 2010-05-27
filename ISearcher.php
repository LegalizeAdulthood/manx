<?php
	require_once 'IFormatter.php';
	
	interface ISearcher
	{
		function renderCompanies($selected);
		function renderSearchResults(IFormatter $formatter, $company, $keywords, $online);
		function matchClauseForKeywords($keywords);
	}
?>
