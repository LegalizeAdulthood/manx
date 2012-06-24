<?php

require_once 'IManx.php';

class FakeManx implements IManx
{
	public function __construct()
	{
		$this->getUserFromSessionCalled = false;
		$this->getSitesCalled = false;
		$this->getSitesFakeResult = array();
		$this->getDatabaseCalled = false;
		$this->addPublicationCalled = false;
		$this->addPublicationFakeResult = -1;
		$this->getCompanyForBitSaversDirectoryCalled = false;
	}

	public function getDetailsForPathInfo($pathInfo) { throw new Exception("getDetailsForPathInfo not implemented"); }
	public function loginUser($user, $password) { throw new Exception("loginUser not implemented"); }
	public function logout() { throw new Exception("logout not implemented"); }
	public function getUserFromSession()
	{
		$this->getUserFromSessionCalled = true;
		return $this->getUserFromSessionFakeResult;
	}
	public $getUserFromSessionCalled;
	public $getUserFromSessionFakeResult;

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

	public function getSites()
	{
		$this->getSitesCalled = true;
		return $this->getSitesFakeResult;
	}
	public $getSitesCalled;
	public $getSitesFakeResult;

	public function getDatabase()
	{
		$this->getDatabaseCalled = true;
		return $this->getDatabaseFakeResult;
	}
	public $getDatabaseCalled;
	public $getDatabaseFakeResult;
}

?>
