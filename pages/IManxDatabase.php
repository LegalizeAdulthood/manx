<?php

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
	function searchForPublications($company, $keywords, $online);
	function getPublicationsSupersededByPub($pubId);
	function getPublicationsSupersedingPub($pubId);
	function getUserId($email, $pw_sha1);
	function createSessionForUser($userId, $sessionId, $remoteHost, $userAgent);
	function getUserFromSessionId($sessionId);
	function getPublicationsForPartNumber($part, $company);
	function addPubHistory($userId, $publicationType, $company, $part,
		$altpart, $revision, $pubDate, $title, $keywords, $notes, $languages);
	function addPublication($pubHistoryId);
	function updatePubHistoryPubId($pubHistoryId, $pubId);
	function getCompanyForId($id);
	function addCompany($fullName, $shortName, $sortName, $display, $notes);
	function updateCompany($id, $fullName, $shortName, $sortName, $display, $notes); 
	function getMirrors();
	function getSites();
	function getFormatForExtension($extension);
	function getCompanyForBitSaversDirectory($dir);
	function deleteUserSession($sessionId);
	function addSupersession($oldPub, $newPub);
	function addSite($name, $url, $description, $copy_base, $low, $live);
	function addCopy($pubId, $format, $siteId, $url,
		$notes, $size, $md5, $credits, $amendSerial);
	function addBitSaversDirectory($companyId, $directory);
	function getMostRecentDocuments($count);
	function getManxVersion();
	function copyExistsForUrl($url);
}

?>
