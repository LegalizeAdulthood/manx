<?php

require_once 'IManxDatabase.php';

class FakeManxDatabase implements IManxDatabase
{
	public function __construct()
	{
		$this->getSiteListCalled = false;
		$this->getCompanyListCalled = false;
		$this->getCompanyListFakeResult = array();
		$this->getDocumentCountCalled = false;
		$this->getOnlineDocumentCountCalled = false;
		$this->getSiteCountCalled = false;
		$this->getDisplayLanguageCalled = false;
		$this->getDisplayLanguageLastLanguage = array();
		$this->getDisplayLanguageFakeResult = array();
		$this->getOSTagsForPubCalled = false;
		$this->getOSTagsForPubFakeResult = array();
		$this->getAmendmentsForPubCalled = false;
		$this->getAmendmentsForPubFakeResult = array();
		$this->getLongDescriptionForPubCalled = false;
		$this->getLongDescriptionForPubFakeResult = array();
		$this->getCitationsForPubCalled = false;
		$this->getCitationsForPubFakeResult = array();
		$this->getTableOfContentsForPubCalled = false;
		$this->getTableOfContentsForPubFakeResult = array();
		$this->getMirrorsForCopyCalled = false;
		$this->getMirrorsForCopyFakeResult = array();
		$this->getAmendedPubCalled = false;
		$this->getAmendedPubFakeResult = array();
		$this->getCopiesForPubCalled = false;
		$this->getCopiesForPubFakeResult = array();
		$this->getDetailsForPubCalled = false;
		$this->getDetailsForPubFakeResult = array();
		$this->searchForPublicationsCalled = false;
		$this->searchForPublicationsFakeResult = array();
		$this->getPublicationsSupersededByPubCalled = false;
		$this->getPublicationsSupersededByPubFakeResult = array();
		$this->getPublicationsSupersedingPubCalled = false;
		$this->getPublicationsSupersedingPubFakeResult = array();
		$this->getSitesCalled = false;
		$this->getSitesFakeResult = array();
		$this->addSupersessionCalled = false;
		$this->addSupersessionFakeResult = -1;
		$this->addCompanyCalled = false;
		$this->addSiteCalled = false;
	}

	public $getDocumentCountCalled, $getDocumentCountFakeResult;
	public function getDocumentCount()
	{
		$this->getDocumentCountCalled = true;
		return $this->getDocumentCountFakeResult;
	}

	public $getOnlineDocumentCountCalled, $getOnlineDocumentCountFakeResult;
	public function getOnlineDocumentCount()
	{
		$this->getOnlineDocumentCountCalled = true;
		return $this->getOnlineDocumentCountFakeResult;
	}

	public $getSiteCountCalled, $getSiteCountFakeResult;
	public function getSiteCount()
	{
		$this->getSiteCountCalled = true;
		return $this->getSiteCountFakeResult;
	}

	public $getSiteListCalled, $getSiteListFakeResult;
	public function getSiteList()
	{
		$this->getSiteListCalled = true;
		return $this->getSiteListFakeResult;
	}

	public $getCompanyListCalled, $getCompanyListFakeResult;
	public function getCompanyList()
	{
		$this->getCompanyListCalled = true;
		return $this->getCompanyListFakeResult;
	}

	public $getDisplayLanguageCalled, $getDisplayLanguageLastLanguageCode, $getDisplayLanguageFakeResult;
	public function getDisplayLanguage($languageCode)
	{
		$this->getDisplayLanguageCalled = true;
		$this->getDisplayLanguageLastLanguageCode[$languageCode] = true;
		return $this->getDisplayLanguageFakeResult[$languageCode];
	}

	public $getOSTagsForPubCalled, $getOSTagsForPubLastPubId, $getOSTagsForPubFakeResult;
	public function getOSTagsForPub($pubId)
	{
		$this->getOSTagsForPubCalled = true;
		$this->getOSTagsForPubLastPubId = $pubId;
		return $this->getOSTagsForPubFakeResult;
	}

	public $getAmendmentsForPubCalled, $getAmendmentsForPubLastPubId, $getAmendmentsForPubFakeResult;
	public function getAmendmentsForPub($pubId)
	{
		$this->getAmendmentsForPubCalled = true;
		$this->getAmendmentsForPubLastPubId = $pubId;
		return $this->getAmendmentsForPubFakeResult;
	}

	public $getLongDescriptionForPubCalled, $getLongDescriptionForPubLastPubId, $getLongDescriptionForPubFakeResult;
	public function getLongDescriptionForPub($pubId)
	{
		$this->getLongDescriptionForPubCalled = true;
		$this->getLongDescriptionForPubLastPubId = $pubId;
		return $this->getLongDescriptionForPubFakeResult;
	}

	public $getCitationsForPubCalled, $getCitationsForPubLastPubId, $getCitationsForPubFakeResult;
	public function getCitationsForPub($pubId)
	{
		$this->getCitationsForPubCalled = true;
		$this->getCitationsForPubLastPubId = $pubId;
		return $this->getCitationsForPubFakeResult;
	}

	public $getTableOfContentsForPubCalled, $getTableOfContentsForPubLastPubId,
		$getTableOfContentsForPubLastFullContents, $getTableOfContentsForPubFakeResult;
	public function getTableOfContentsForPub($pubId, $fullContents)
	{
		$this->getTableOfContentsForPubCalled = true;
		$this->getTableOfContentsForPubLastPubId = $pubId;
		$this->getTableOfContentsForPubLastFullContents = $fullContents;
		return $this->getTableOfContentsForPubFakeResult;
	}

	public $getMirrorsForCopyCalled, $getMirrorsForCopyLastCopyId, $getMirrorsForCopyFakeResult;
	public function getMirrorsForCopy($copyId)
	{
		$this->getMirrorsForCopyCalled = true;
		$this->getMirrorsForCopyLastCopyId = $copyId;
		return $this->getMirrorsForCopyFakeResult[$copyId];
	}

	public $getAmendedPubCalled, $getAmendedPubLastPubId, $getAmendedPubLastAmendSerial, $getAmendedPubFakeResult;
	public function getAmendedPub($pubId, $amendSerial)
	{
		$this->getAmendedPubCalled = true;
		$this->getAmendedPubLastPubId = $pubId;
		$this->getAmendedPubLastAmendSerial = $amendSerial;
		return $this->getAmendedPubFakeResult;
	}

	public $getCopiesForPubCalled, $getCopiesForPubLastPubId, $getCopiesForPubFakeResult;
	public function getCopiesForPub($pubId)
	{
		$this->getCopiesForPubCalled = true;
		$this->getCopiesForPubLastPubId = $pubId;
		return $this->getCopiesForPubFakeResult;
	}

	public $getDetailsForPubCalled, $getDetailsForPubLastPubId, $getDetailsForPubFakeResult;
	public function getDetailsForPub($pubId)
	{
		$this->getDetailsForPubCalled = true;
		$this->getDetailsForPubLastPubId = $pubId;
		return $this->getDetailsForPubFakeResult;
	}

	public $searchForPublicationsCalled, $searchForPublicationsFakeResult,
		$searchForPublicationsLastCompany, $searchForPublicationsLastKeywords, $searchForPublicationsLastOnline;
	public function searchForPublications($company, $keywords, $online)
	{
		$this->searchForPublicationsCalled = true;
		$this->searchForPublicationsLastCompany = $company;
		$this->searchForPublicationsLastKeywords = $keywords;
		$this->searchForPublicationsLastOnline = $online;
		return $this->searchForPublicationsFakeResult;
	}

	public $getPublicationsSupersededByPubCalled, $getPublicationsSupersededByPubLastPubId, $getPublicationsSupersededByPubFakeResult;
	function getPublicationsSupersededByPub($pubId)
	{
		$this->getPublicationsSupersededByPubCalled = true;
		$this->getPublicationsSupersededByPubLastPubId = $pubId;
		return $this->getPublicationsSupersededByPubFakeResult;
	}

	public $getPublicationsSupersedingPubCalled, $getPublicationsSupersedingPubLastPubId, $getPublicationsSupersedingPubFakeResult;
	function getPublicationsSupersedingPub($pubId)
	{
		$this->getPublicationsSupersedingPubCalled = true;
		$this->getPublicationsSupersedingPubLastPubId = $pubId;
		return $this->getPublicationsSupersedingPubFakeResult;
	}

	private function notImplemented($name)
	{
		throw new Exception($name . " not implemented");
	}
	public function getUserId($email, $pw_sha1)
	{
		$this->notImplemented("getUserId");
	}
	public function createSessionForUser($userId, $sessionId, $remoteHost, $userAgent)
	{
		$this->notImplemented("createSessionForUser");
	}
	public function deleteSessionById($sessionId)
	{
		$this->notImplemented("deleteSessionById");
	}
	public function getUserFromSessionId($sessionId)
	{
		$this->notImplemented("getUserFromSessionId");
	}
	public function getPublicationsForPartNumber($part, $company)
	{
		$this->notImplemented("getPublicationsForPartNumber");
	}
	public function addPubHistory($userId, $publicationType, $company, $part,
		$altpart, $revision, $pubDate, $title, $keywords, $notes, $languages)
	{
		$this->notImplemented("addPubHistory");
	}
	public function addPublication($pubHistoryId)
	{
		$this->notImplemented("addPublication");
	}
	public function updatePubHistoryPubId($pubHistoryId, $pubId)
	{
		$this->notImplemented("updatePubHistoryPubId");
	}
	public function getCompanyForId($id)
	{
		$this->notImplemented("getCompanyForId");
	}
	public function addCompany($fullName, $shortName, $sortName, $display, $notes)
	{
		$this->addCompanyCalled = true;
	}
	public function updateCompany($id, $fullName, $shortName, $sortName, $display, $notes)
	{
		$this->notImplemented("updateCompany");
	} 
	public function getMirrors()
	{
		$this->notImplemented("getMirrors");
	}
	public function getSites()
	{
		$this->getSitesCalled = true;
		return $this->getSitesFakeResult;
	}
	public function getFormatForExtension($extension)
	{
		$this->notImplemented("getFormatForExtension");
	}
	public function getCompanyForBitSaversDirectory($dir)
	{
		$this->notImplemented("getCompanyForBitSaversDirectory");
	}
	public function deleteUserSession($sessionId)
	{
		$this->notImplemented("deleteUserSession");
	}

	function addSupersession($oldPub, $newPub)
	{
		$this->addSupersessionCalled = true;
		$this->addSupersessionLastOldPub = $oldPub;
		$this->addSupersessionLastNewPub = $newPub;
		return $this->addSupersessionFakeResult;
	}
	public $addSupersessionCalled,
		$addSupersessionLastOldPub, $addSupersessionLastNewPub,
		$addSupersessionFakeResult;
	function addSite($name, $url, $description, $copy_base, $low, $live)
	{
		$this->addSiteCalled = true;
	}
	public $addSiteCalled;
	function addCopy($pubId, $format, $siteId, $url,
		$notes, $size, $md5, $credits, $amendSerial)
	{
		$this->addCopyCalled = true;
		$this->addCopyLastPubId = $pubId;
		$this->addCopyLastFormat = $format;
		$this->addCopyLastSiteId = $siteId;
		$this->addCopyLastUrl = $url;
		$this->addCopyLastNotes = $notes;
		$this->addCopyLastSize = $size;
		$this->addCopyLastMd5 = $md5;
		$this->addCopyLastCredits = $credits;
		$this->addCopyLastAmendSerial = $amendSerial;
		return $this->addCopyFakeResult;
	}
	public $addCopyCalled,
		$addCopyLastPubId, $addCopyLastFormat,
			$addCopyLastSiteId, $addCopyLastUrl,
			$addCopyLastNotes, $addCopyLastSize,
			$addCopyLastMd5, $addCopyLastCredits,
			$addCopyLastAmendSerial,
		$addCopyFakeResult;

	function addBitSaversDirectory($companyId, $directory)
	{
		$this->notImplemented("addBitSaversDirectory");
	}
}

?>
