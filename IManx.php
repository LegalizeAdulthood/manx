<?php

interface IManx
{
	public function getDatabase();
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
	public function getFormatForExtension($extension);
	public function getCompanyForBitSaversDirectory($dir);
	public function getPublicationsForPartNumber($part, $companyId);
}

?>
