<?php
	interface ISearcher
	{
		function renderCompanies($selected);
		function renderSearchResults($company, $keywords, $online);
		function matchClauseForKeywords($keywords);
	}
?>
