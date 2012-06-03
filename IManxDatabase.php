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
		function deleteSessionById($sessionId);
		function getUserFromSessionId($sessionId);
		function getPublicationsForPartNumber($part, $company);
		function addPubHistory($userId, $publicationType, $company, $part,
			$altpart, $revision, $pubDate, $title, $keywords, $notes, $languages);
		function addPublication($pubHistoryId);
		function updatePubHistoryPubId($pubHistoryId, $pubId);
	}
?>
