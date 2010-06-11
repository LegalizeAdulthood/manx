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
	}
?>
