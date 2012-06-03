<?php

interface IManx
{
	public function renderAuthorization();
	public function renderDocumentSummary();
	public function renderCompanyList();
	public function renderSearchResults();
	public function getDetailsForPathInfo($pathInfo);
	public function renderDetails($details);
	public function renderLanguage($lang);
	public function renderAmendments($pubId);
	public function renderOSTags($pubId);
	public function renderLongDescription($pubId);
	public function renderCitations($pubId);
	public function renderSupersessions($pubId);
	public function renderTableOfContents($pubIdm, $fullContents);
	public function renderCopies($pubId);
	public function loginUser($user, $password);
	public function logout();
}

?>
