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
	}
?>