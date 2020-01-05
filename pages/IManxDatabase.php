<?php

namespace Manx;

interface IManxDatabase
{
    function getDocumentCount();
    function getOnlineDocumentCount();
    function getSiteCount();
    function getSiteList();
    function getCompanyList();
    function getDisplayLanguage($languageCode);
    function getOSTagsForPub($pubId);
    function getAmendmentsForPub($pubId);
    function getLongDescriptionForPub($pubId);
    function getCitationsForPub($pubId);
    function getTableOfContentsForPub($pubId, $fullContents);
    function getMirrorsForCopy($copyId);
    function getAmendedPub($pubId, $amendSerial);
    function getCopiesForPub($pubId);
    function getDetailsForPub($pubId);
    function searchForPublications($company, array $keywords, $online);
    function getPublicationsSupersededByPub($pubId);
    function getPublicationsSupersedingPub($pubId);
    function getUserId($email, $pw_sha1);
    function createSessionForUser($userId, $sessionId, $remoteHost, $userAgent);
    function getUserFromSessionId($sessionId);
    function getPublicationsForPartNumber($part, $companyId);
    function addPubHistory($userId, $publicationType, $companyId, $part,
        $altPart, $revision, $pubDate, $title, $keywords, $notes, $abstract,
        $languages);
    function addPublication($pubHistoryId);
    function updatePubHistoryPubId($pubHistoryId, $pubId);
    function getCompanyForId($companyId);
    function addCompany($fullName, $shortName, $sortName, $display, $notes);
    function updateCompany($companyId, $fullName, $shortName, $sortName, $display, $notes);
    function getMirrors();
    function getSites();
    function getFormatForExtension($extension);
    function getCompanyIdForSiteDirectory($siteName, $dir, $parentDir);
    function deleteUserSession($session);
    function addSupersession($oldPub, $newPub);
    function addSite($name, $url, $description, $copy_base, $low, $live);
    function addCopy($pubId, $format, $siteId, $url,
        $notes, $size, $md5, $credits, $amendSerial);
    function addSiteDirectory($siteName, $companyId, $directory, $parentDirectory);
    function getMostRecentDocuments($count);
    function getManxVersion();
    function copyExistsForUrl($url);
    function getZeroSizeDocuments();
    function getUrlForCopy($copyId);
    function updateSizeForCopy($copyId, $size);
    function updateMD5ForCopy($copyId, $md5);
    function getMissingMD5Documents();
    function getAllMissingMD5Documents();
    function getProperty($name);
    function setProperty($name, $value);
    function addSiteUnknownPaths($siteName, array $paths);
    function ignoreSitePaths(array $ignoredIds);
    function getSiteUnknownPathCount($siteName);
    function getSiteUnknownPathsOrderedById($siteName, $start, $ascending);
    function getSiteUnknownPathsOrderedByPath($siteName, $start, $ascending);
    function getAllSiteUnknownPaths($siteName);
    function removeSiteUnknownPathById($siteUnknownId);
    function getPossiblyMovedSiteUnknownPaths($siteName);
    function siteFileMoved($pathId, $copyId, $url);
    function removeUnknownPathsWithCopy();
    function getUnknownPathsForCompanies($siteName);
    function markUnknownPathScanned($unknownId);
    function getIngestionRobotUser();
    function setSiteLive($siteId, $liveNotDead);
    function getSampleCopiesForSite($siteId);
    function getSiteUnknownDirectories($siteName, $parentDirId);
    function getSiteUnknownPaths($siteName, $parentDirId);
    function getSiteUnknownDir($dirId);
    function updateIgnoredUnknownDirs();
}
