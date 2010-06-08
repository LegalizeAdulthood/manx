<?php

interface IManx
{
	public function renderLoginLink($page);
	public function renderDocumentSummary();
	public function renderCompanyList();
	public function renderSearchResults();
	public function renderDetails($pathInfo);
	public function renderLanguage($lang);
	public function renderAmendments($pubId);
	public function renderOSTags($pubId);
	public function renderLongDescription($pubId);
	public function renderCitations($pubId);
	public function renderSupersessions($pubId);
	public function renderTableOfContents($pubIdm, $fullContents);
	public function renderCopies($pubId);
}

?>
