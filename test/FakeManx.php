<?php

require_once 'IManx.php';

class FakeManx implements IManx
{
	public function __construct()
	{
		$this->getUserFromSessionCalled = false;
		$this->getSitesCalled = false;
		$this->getSitesFakeResult = array();
		$this->getCompanyListCalled = false;
		$this->getCompanyListFakeResult = array();
		$this->getDatabaseCalled = false;
		$this->addPublicationCalled = false;
		$this->addPublicationFakeResult = -1;
		$this->getMirrorsCalled = false;
		$this->getMirrorsFakeResult = array();
		$this->getCompanyForBitSaversDirectoryCalled = false;
		$this->getPublicationsForPartNumberCalled = false;
		$this->getPublicationsForPartNumberFakeResult = array();
		$this->getFormatForExtensionCalled = false;
	}

	public function renderAuthorization() { throw new Exception("renderAuthorization not implemented"); }
	public function renderDocumentSummary() { throw new Exception("renderDocumentSummary not implemented"); }
	public function renderCompanyList() { throw new Exception("renderCompanyList not implemented"); }
	public function renderSearchResults() { throw new Exception("renderSearchResults not implemented"); }
	public function getDetailsForPathInfo($pathInfo) { throw new Exception("getDetailsForPathInfo not implemented"); }
	public function renderDetails($details) { throw new Exception("renderDetails not implemented"); }
	public function renderLanguage($lang) { throw new Exception("renderLanguage not implemented"); }
	public function renderAmendments($pubId) { throw new Exception("renderAmendments not implemented"); }
	public function renderOSTags($pubId) { throw new Exception("renderOSTags not implemented"); }
	public function renderLongDescription($pubId) { throw new Exception("renderLongDescription not implemented"); }
	public function renderCitations($pubId) { throw new Exception("renderCitations not implemented"); }
	public function renderSupersessions($pubId) { throw new Exception("renderSupersessions not implemented"); }
	public function renderTableOfContents($pubIdm, $fullContents) { throw new Exception("renderTableOfContents not implemented"); }
	public function renderCopies($pubId) { throw new Exception("renderCopies not implemented"); }
	public function loginUser($user, $password) { throw new Exception("loginUser not implemented"); }
	public function logout() { throw new Exception("logout not implemented"); }
	public function getUserFromSession()
	{
		$this->getUserFromSessionCalled = true;
		return $this->getUserFromSessionFakeResult;
	}
	public $getUserFromSessionCalled;
	public $getUserFromSessionFakeResult;

	public function getCompanyList()
	{
		$this->getCompanyListCalled = true;
		return $this->getCompanyListFakeResult;
	}
	public $getCompanyListCalled;
	public $getCompanyListFakeResult;
	public function addPublication($user, $company, $part, $pubDate, $title,
		$publicationType, $altPart, $revision, $keywords, $notes, $languages)
	{
		$this->addPublicationCalled = true;
		$this->addPublicationLastUser = $user;
		$this->addPublicationLastCompany = $company;
		$this->addPublicationLastPart = $part;
		$this->addPublicationLastPubDate = $pubDate;
		$this->addPublicationLastTitle = $title;
		$this->addPublicationLastPublicationType = $publicationType;
		$this->addPublicationLastAltPart = $altPart;
		$this->addPublicationLastRevision = $revision;
		$this->addPublicationLastKeywords = $keywords;
		$this->addPublicationLastNotes = $notes;
		$this->addPublicationLastLanguages = $languages;
		return $this->addPublicationFakeResult;
	}
	public $addPublicationCalled;
	public $addPublicationLastUser, $addPublicationLastCompany,
		$addPublicationLastPart, $addPublicationLastPubDate,
		$addPublicationLastTitle, $addPublicationLastPublicationType,
		$addPublicationLastAltPart, $addPublicationLastRevision,
		$addPublicationLastKeywords, $addPublicationLastNotes,
		$addPublicationLastLanguages;
	public $addPublicationFakeResult;
	public function getCompanyForId($id) { throw new Exception("getCompanyForId not implemented"); }
	public function addCompany($fullName, $shortName, $sortName, $display, $notes) { throw new Exception("addCompany not implemented"); }
	public function updateCompany($id, $fullName, $shortName, $sortName, $display, $notes) { throw new Exception("updateCompany not implemented"); }
	public function getMirrors()
	{
		$this->getMirrorsCalled = true;
		return $this->getMirrorsFakeResult;
	}
	public $getMirrorsCalled, $getMirrorsFakeResult;
	public function getSites()
	{
		$this->getSitesCalled = true;
		return $this->getSitesFakeResult;
	}
	public $getSitesCalled;
	public $getSitesFakeResult;
	public function getFormatForExtension($extension)
	{
		$this->getFormatForExtensionCalled = true;
		$this->getFormatForExtensionLastExtension = $extension;
		return $this->getFormatForExtensionFakeResult;
	}
	public $getFormatForExtensionCalled,
		$getFormatForExtensionLastExtension,
		$getFormatForExtensionFakeResult;
	public function getCompanyForBitSaversDirectory($dir)
	{
		$this->getCompanyForBitSaversDirectoryCalled = true;
		$this->getCompanyForBitSaversDirectoryLastDir = $dir;
		return $this->getCompanyForBitSaversDirectoryFakeResult;
	}
	public $getCompanyForBitSaversDirectoryCalled,
		$getCompanyForBitSaversDirectoryLastDir,
		$getCompanyForBitSaversDirectoryFakeResult;
	public function getPublicationsForPartNumber($part, $companyId)
	{
		$this->getPublicationsForPartNumberCalled = true;
		$this->getPublicationsForPartNumberLastPart = $part;
		$this->getPublicationsForPartNumberLastCompanyId = $companyId;
		return $this->getPublicationsForPartNumberFakeResult;
	}
	public $getPublicationsForPartNumberCalled,
		$getPublicationsForPartNumberLastPart,
		$getPublicationsForPartNumberLastCompanyId,
		$getPublicationsForPartNumberFakeResult;
	public function getDatabase()
	{
		$this->getDatabaseCalled = true;
		return $this->getDatabaseFakeResult;
	}
	public $getDatabaseCalled;
	public $getDatabaseFakeResult;
}

?>
