<?php

require_once 'pages/IManxDatabase.php';

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
        $this->getCompanyForBitSaversDirectoryCalled = false;
        $this->getPublicationsForPartNumberCalled = false;
        $this->getPublicationsForPartNumberFakeResult = array();
        $this->getFormatForExtensionCalled = false;
        $this->getMirrorsCalled = false;
        $this->getMirrorsFakeResult = array();
        $this->getMostRecentDocumentsCalled = false;
        $this->getMostRecentDocumentsFakeResult = array();
        $this->copyExistsForUrlCalled = false;
        $this->copyExistsForUrlFakeResult = false;
        $this->copyExistsForUrlFakeResults = array();
        $this->bitSaversIgnoredPathCalled = false;
        $this->bitSaversIgnoredPathFakeResult = false;
        $this->addBitSaversUnknownPathCalled = false;
        $this->addBitSaversUnknownPathLastPaths = array();
        $this->getBitSaversUnknownPathsOrderedByIdCalled = false;
        $this->getBitSaversUnknownPathsOrderedByIdFakeResult = array();
        $this->ignoreBitSaversPathCalled = false;
        $this->setPropertyCalled = false;
        $this->getPropertyCalled = false;
        $this->getMissingMD5DocumentsCalled = false;
        $this->updateMD5ForCopyCalled = false;
        $this->updateSizeForCopyCalled = false;
        $this->getUrlForCopyCalled = false;
        $this->getZeroSizeDocumentsCalled = false;
        $this->getAllBitSaversUnknownPathsCalled = false;
        $this->getAllBitSaversUnknownPathsResult = array();
        $this->removeBitSaversUnknownPathByIdCalled = false;
        $this->removeBitSaversUnknownPathByIdLastId = -1;
        $this->getPossiblyMovedUnknownPathsCalled = false;
        $this->getPossiblyMovedUnknownPathsFakeResult = array();
        $this->bitsaversFileMovedCalled = false;
    }

    public function getDocumentCount()
    {
        $this->getDocumentCountCalled = true;
        return $this->getDocumentCountFakeResult;
    }
    public $getDocumentCountCalled, $getDocumentCountFakeResult;

    public function getOnlineDocumentCount()
    {
        $this->getOnlineDocumentCountCalled = true;
        return $this->getOnlineDocumentCountFakeResult;
    }
    public $getOnlineDocumentCountCalled, $getOnlineDocumentCountFakeResult;

    public function getSiteCount()
    {
        $this->getSiteCountCalled = true;
        return $this->getSiteCountFakeResult;
    }
    public $getSiteCountCalled, $getSiteCountFakeResult;

    public function getSiteList()
    {
        $this->getSiteListCalled = true;
        return $this->getSiteListFakeResult;
    }
    public $getSiteListCalled, $getSiteListFakeResult;

    public function getCompanyList()
    {
        $this->getCompanyListCalled = true;
        return $this->getCompanyListFakeResult;
    }
    public $getCompanyListCalled, $getCompanyListFakeResult;

    public function getDisplayLanguage($languageCode)
    {
        $this->getDisplayLanguageCalled = true;
        $this->getDisplayLanguageLastLanguageCode[$languageCode] = true;
        return $this->getDisplayLanguageFakeResult[$languageCode];
    }
    public $getDisplayLanguageCalled, $getDisplayLanguageLastLanguageCode, $getDisplayLanguageFakeResult;

    public function getOSTagsForPub($pubId)
    {
        $this->getOSTagsForPubCalled = true;
        $this->getOSTagsForPubLastPubId = $pubId;
        return $this->getOSTagsForPubFakeResult;
    }
    public $getOSTagsForPubCalled, $getOSTagsForPubLastPubId, $getOSTagsForPubFakeResult;

    public function getAmendmentsForPub($pubId)
    {
        $this->getAmendmentsForPubCalled = true;
        $this->getAmendmentsForPubLastPubId = $pubId;
        return $this->getAmendmentsForPubFakeResult;
    }
    public $getAmendmentsForPubCalled, $getAmendmentsForPubLastPubId, $getAmendmentsForPubFakeResult;

    public function getLongDescriptionForPub($pubId)
    {
        $this->getLongDescriptionForPubCalled = true;
        $this->getLongDescriptionForPubLastPubId = $pubId;
        return $this->getLongDescriptionForPubFakeResult;
    }
    public $getLongDescriptionForPubCalled, $getLongDescriptionForPubLastPubId, $getLongDescriptionForPubFakeResult;

    public function getCitationsForPub($pubId)
    {
        $this->getCitationsForPubCalled = true;
        $this->getCitationsForPubLastPubId = $pubId;
        return $this->getCitationsForPubFakeResult;
    }
    public $getCitationsForPubCalled, $getCitationsForPubLastPubId, $getCitationsForPubFakeResult;

    public function getTableOfContentsForPub($pubId, $fullContents)
    {
        $this->getTableOfContentsForPubCalled = true;
        $this->getTableOfContentsForPubLastPubId = $pubId;
        $this->getTableOfContentsForPubLastFullContents = $fullContents;
        return $this->getTableOfContentsForPubFakeResult;
    }
    public $getTableOfContentsForPubCalled, $getTableOfContentsForPubLastPubId,
        $getTableOfContentsForPubLastFullContents, $getTableOfContentsForPubFakeResult;

    public function getMirrorsForCopy($copyId)
    {
        $this->getMirrorsForCopyCalled = true;
        $this->getMirrorsForCopyLastCopyId = $copyId;
        return $this->getMirrorsForCopyFakeResult[$copyId];
    }
    public $getMirrorsForCopyCalled, $getMirrorsForCopyLastCopyId, $getMirrorsForCopyFakeResult;

    public function getAmendedPub($pubId, $amendSerial)
    {
        $this->getAmendedPubCalled = true;
        $this->getAmendedPubLastPubId = $pubId;
        $this->getAmendedPubLastAmendSerial = $amendSerial;
        return $this->getAmendedPubFakeResult;
    }
    public $getAmendedPubCalled, $getAmendedPubLastPubId, $getAmendedPubLastAmendSerial, $getAmendedPubFakeResult;

    public function getCopiesForPub($pubId)
    {
        $this->getCopiesForPubCalled = true;
        $this->getCopiesForPubLastPubId = $pubId;
        return $this->getCopiesForPubFakeResult;
    }
    public $getCopiesForPubCalled, $getCopiesForPubLastPubId, $getCopiesForPubFakeResult;

    public function getDetailsForPub($pubId)
    {
        $this->getDetailsForPubCalled = true;
        $this->getDetailsForPubLastPubId = $pubId;
        return $this->getDetailsForPubFakeResult;
    }
    public $getDetailsForPubCalled, $getDetailsForPubLastPubId, $getDetailsForPubFakeResult;

    public function searchForPublications($company, $keywords, $online)
    {
        $this->searchForPublicationsCalled = true;
        $this->searchForPublicationsLastCompany = $company;
        $this->searchForPublicationsLastKeywords = $keywords;
        $this->searchForPublicationsLastOnline = $online;
        return $this->searchForPublicationsFakeResult;
    }
    public $searchForPublicationsCalled,
        $searchForPublicationsLastCompany, $searchForPublicationsLastKeywords, $searchForPublicationsLastOnline,
        $searchForPublicationsFakeResult;

    function getPublicationsSupersededByPub($pubId)
    {
        $this->getPublicationsSupersededByPubCalled = true;
        $this->getPublicationsSupersededByPubLastPubId = $pubId;
        return $this->getPublicationsSupersededByPubFakeResult;
    }
    public $getPublicationsSupersededByPubCalled,
        $getPublicationsSupersededByPubLastPubId,
        $getPublicationsSupersededByPubFakeResult;

    function getPublicationsSupersedingPub($pubId)
    {
        $this->getPublicationsSupersedingPubCalled = true;
        $this->getPublicationsSupersedingPubLastPubId = $pubId;
        return $this->getPublicationsSupersedingPubFakeResult;
    }
    public $getPublicationsSupersedingPubCalled,
        $getPublicationsSupersedingPubLastPubId,
        $getPublicationsSupersedingPubFakeResult;

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

    public function addPubHistory($userId, $publicationType, $company, $part,
        $altPart, $revision, $pubDate, $title, $keywords, $notes, $abstract,
        $languages)
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
        $this->getMirrorsCalled = true;
        return $this->getMirrorsFakeResult;
    }
    public $getMirrorsCalled, $getMirrorsFakeResult;

    public function getSites()
    {
        $this->getSitesCalled = true;
        return $this->getSitesFakeResult;
    }

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

    function getMostRecentDocuments($count)
    {
        $this->getMostRecentDocumentsCalled = true;
        $this->getMostRecentDocumentsLastCount = $count;
        return $this->getMostRecentDocumentsFakeResult;
    }
    public $getMostRecentDocumentsCalled,
        $getMostRecentDocumentsLastCount,
        $getMostRecentDocumentsFakeResult;

    function getManxVersion()
    {
        $this->notImplemented("getManxVersion");
    }

    function copyExistsForUrl($url)
    {
        $this->copyExistsForUrlCalled = true;
        $this->copyExistsForUrlLastUrl = $url;
        if (array_key_exists($url, $this->copyExistsForUrlFakeResults))
        {
            return $this->copyExistsForUrlFakeResults[$url];
        }
        return $this->copyExistsForUrlFakeResult;
    }
    public $copyExistsForUrlCalled,
        $copyExistsForUrlLastUrl,
        $copyExistsForUrlFakeResult, $copyExistsForUrlFakeResults;

    function getZeroSizeDocuments()
    {
        $this->getZeroSizeDocumentsCalled = true;
        return $this->getZeroSizeDocumentsFakeResult;
    }
    public $getZeroSizeDocumentsCalled,
        $getZeroSizeDocumentsFakeResult;

    function getUrlForCopy($copyId)
    {
        $this->getUrlForCopyCalled = true;
        $this->getUrlForCopyLastCopyId = $copyId;
        return $this->getUrlForCopyFakeResult;
    }
    public $getUrlForCopyCalled,
        $getUrlForCopyLastCopyId,
        $getUrlForCopyFakeResult;

    function updateSizeForCopy($copyId, $size)
    {
        $this->updateSizeForCopyCalled = true;
        $this->updateSizeForCopyLastCopyId = $copyId;
        $this->updateSizeForCopyLastSize = $size;
    }
    public $updateSizeForCopyCalled,
        $updateSizeForCopyLastCopyId,
        $updateSizeForCopyLastSize;

    function updateMD5ForCopy($copyId, $md5)
    {
        $this->updateMD5ForCopyCalled = true;
        $this->updateMD5ForCopyLastCopyId = $copyId;
        $this->updateMD5ForCopyLastMD5 = $md5;
    }
    public $updateMD5ForCopyCalled,
        $updateMD5ForCopyLastCopyId, $updateMD5ForCopyLastMD5;

    function getMissingMD5Documents()
    {
        $this->getMissingMD5DocumentsCalled;
        return $this->getMissingMD5DocumentsFakeResult;
    }
    public $getMissingMD5DocumentsCalled,
        $getMissingMD5DocumentsFakeResult;

    function getProperty($name)
    {
        $this->getPropertyCalled = true;
        $this->getPropertyLastName = $name;
        return $this->getPropertyFakeResult;
    }
    public $getPropertyCalled, $getPropertyLastName, $getPropertyFakeResult;

    function setProperty($name, $value)
    {
        $this->setPropertyCalled = true;
        $this->setPropertyLastName = $name;
        $this->setPropertyLastValue = $value;
    }
    public $setPropertyCalled, $setPropertyLastName, $setPropertyLastValue;

    function addBitSaversUnknownPath($path)
    {
        $this->addBitSaversUnknownPathCalled = true;
        $this->addBitSaversUnknownPathLastPaths[] = $path;
    }
    public $addBitSaversUnknownPathCalled, $addBitSaversUnknownPathLastPaths;

    function ignoreBitSaversPath($path)
    {
        $this->ignoreBitSaversPathCalled = true;
        $this->ignoreBitSaversPathLastPath = $path;
    }
    public $ignoreBitSaversPathCalled, $ignoreBitSaversPathLastPath;

    function getBitSaversUnknownPathCount()
    {
        $this->getBitSaversUnknownPathCountCalled = true;
        return $this->getBitSaversUnknownPathCountFakeResult;
    }
    public $getBitSaversUnknownPathCountCalled, $getBitSaversUnknownPathCountFakeResult;

    function getBitSaversUnknownPathsOrderedById($start, $ascending)
    {
        $this->getBitSaversUnknownPathsOrderedByIdCalled = true;
        $this->getBitSaversUnknownPathsOrderedByIdLastStart = $start;
        $this->getBitSaversUnknownPathsOrderedByIdLastAscending = $ascending;
        return $this->getBitSaversUnknownPathsOrderedByIdFakeResult;
    }
    public $getBitSaversUnknownPathsOrderedByIdCalled,
        $getBitSaversUnknownPathsOrderedByIdLastStart,
        $getBitSaversUnknownPathsOrderedByIdLastAscending,
        $getBitSaversUnknownPathsOrderedByIdFakeResult;

    function getBitSaversUnknownPathsOrderedByPath($start, $ascending)
    {
        $this->getBitSaversUnknownPathsOrderedByPathCalled = true;
        $this->getBitSaversUnknownPathsOrderedByPathLastStart = $start;
        $this->getBitSaversUnknownPathsOrderedByPathLastAscending = $ascending;
        return $this->getBitSaversUnknownPathsOrderedByPathFakeResult;
    }
    public $getBitSaversUnknownPathsOrderedByPathCalled,
        $getBitSaversUnknownPathsOrderedByPathLastStart,
            $getBitSaversUnknownPathsOrderedByPathLastAscending,
        $getBitSaversUnknownPathsOrderedByPathFakeResult;

    function bitSaversIgnoredPath($path)
    {
        $this->bitSaversIgnoredPathCalled = true;
        $this->bitSaversIgnoredPathLastPath = $path;
        return $this->bitSaversIgnoredPathFakeResult;
    }
    public $bitSaversIgnoredPathCalled, $bitSaversIgnoredPathLastPath,
        $bitSaversIgnoredPathFakeResult;

    function getAllBitSaversUnknownPaths()
    {
        $this->getAllBitSaversUnknownPathsCalled = true;
        return $this->getAllBitSaversUnknownPathsResult;
    }
    public $getAllBitSaversUnknownPathsCalled, $getAllBitSaversUnknownPathsResult;

    function removeBitSaversUnknownPathById($id)
    {
        $this->removeBitSaversUnknownPathByIdCalled = true;
        $this->removeBitSaversUnknownPathByIdLastId = $id;
    }
    public $removeBitSaversUnknownPathByIdCalled, $removeBitSaversUnknownPathByIdLastId;

    function getPossiblyMovedUnknownPaths()
    {
        $this->getPossiblyMovedUnknownPathsCalled = true;
        return $this->getPossiblyMovedUnknownPathsFakeResult;
    }
    public $getPossiblyMovedUnknownPathsCalled, $getPossiblyMovedUnknownPathsFakeResult;

    function bitsaversFileMoved($copyId, $pathId, $url)
    {
        $this->bitsaversFileMovedCalled = true;
        $this->bitsaversFileMovedLastCopyId = $copyId;
        $this->bitsaversFileMovedLastPathId = $pathId;
        $this->bitsaversFileMovedLastUrl = $url;
    }
    public $bitsaversFileMovedCalled, $bitsaversFileMovedLastCopyId, $bitsaversFileMovedLastPathId, $bitsaversFileMovedLastUrl;
}
