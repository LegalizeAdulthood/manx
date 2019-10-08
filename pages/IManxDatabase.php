<?php

interface IManxDatabase
{
    function getDocumentCount();
    function getOnlineDocumentCount();
    function getSiteCount();
    function getSiteList();
    function getCompanyList();
    function getDisplayLanguage(string $languageCode);
    function getOSTagsForPub(int $pubId);
    function getAmendmentsForPub(int $pubId);
    function getLongDescriptionForPub(int $pubId);
    function getCitationsForPub(int $pubId);
    function getTableOfContentsForPub(int $pubId, bool $fullContents);
    function getMirrorsForCopy(int $copyId);
    function getAmendedPub(int $pubId, int $amendSerial);
    function getCopiesForPub(int $pubId);
    function getDetailsForPub(int $pubId);
    function searchForPublications(int $company, array $keywords, bool $online);
    function getPublicationsSupersededByPub(int $pubId);
    function getPublicationsSupersedingPub(int $pubId);
    function getUserId(string $email, string $pw_sha1);
    function createSessionForUser(int $userId, int $sessionId, string $remoteHost, string $userAgent);
    function getUserFromSessionId(int $sessionId);
    function getPublicationsForPartNumber(string $part, int $companyId);
    function addPubHistory(int $userId, string $publicationType, int $companyId, string $part,
        string $altPart, string $revision, string $pubDate, string $title, string $keywords, string $notes, string $abstract,
        string $languages);
    function addPublication(int $pubHistoryId);
    function updatePubHistoryPubId(int $pubHistoryId, int $pubId);
    function getCompanyForId(int $companyId);
    function addCompany(string $fullName, string $shortName, string $sortName, string $display, string $notes);
    function updateCompany(int $companyId, string $fullName, string $shortName, string $sortName, string $display, string $notes);
    function getMirrors();
    function getSites();
    function getFormatForExtension(string $extension);
    function getCompanyForSiteDirectory(string $siteName, string $dir);
    function deleteUserSession(string $session);
    function addSupersession(int $oldPub, int $newPub);
    function addSite(string $name, string $url, string $description, string $copy_base, string $low, string $live);
    function addCopy($pubId, string $format, int $siteId, string $url,
        string $notes, $size, string $md5, string $credits, $amendSerial);
    function addSiteDirectory(string $siteName, int $companyId, string $directory);
    function getMostRecentDocuments(int $count);
    function getManxVersion();
    function copyExistsForUrl(string $url);
    function getZeroSizeDocuments();
    function getUrlForCopy(int $copyId);
    function updateSizeForCopy(int $copyId, int $size);
    function updateMD5ForCopy(int $copyId, string $md5);
    function getMissingMD5Documents();
    function getProperty(string $name);
    function setProperty(string $name, $value);
    function addSiteUnknownPath(string $siteName, string $path);
    function ignoreSitePath(string $siteName, string $path);
    function getSiteUnknownPathCount(string $siteName);
    function getSiteUnknownPathsOrderedById(string $siteName, int $start, bool $ascending);
    function getSiteUnknownPathsOrderedByPath(string $siteName, int $start, bool $ascending);
    function siteIgnoredPath(string $siteName, string $path);
    function getAllSiteUnknownPaths(string $siteName);
    function removeSiteUnknownPathById(string $siteName, int $siteUnknownId);
    function getPossiblyMovedSiteUnknownPaths(string $siteName);
    function siteFileMoved(string $siteName, int $copyId, int $pathId, string $url);
}
