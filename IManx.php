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
	public function getUserFromSession();
	public function getCompanyList();
	public function addPublication($user, $company, $part, $pubDate, $title,
		$publicationType, $altPart, $revision, $keywords, $notes, $languages);
	public function getCompanyForId($id);
	public function addCompany($fullName, $shortName, $sortName, $display, $notes);
	public function updateCompany($id, $fullName, $shortName, $sortName, $display, $notes);
	public function getMirrors();
	public function getSites();
}

?>
