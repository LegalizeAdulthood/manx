<?php

/*

1. Enter URL and submit to wizard for analysis.
2. Wizard does the following:
   a. Look at prefix of URL and try to associate a mirror or site with
      the URL.
   b. Look at directory components of the URL and try to match them
      against short names for companies to identify the company.
   c. Look at the prefix of the last component of the URL and attempt to
      extract a proposed part number
   d. Fetch document and compute size and MD5 (requires PECL http)
   e. Extract a proposed title from the last component of the URL
   f. Identify a possible new site if URL doesn't match any known site
      or mirror.
3. Show all guesses at information for editing before submitting a
   real change.

*/

require_once 'UrlWizardPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new URLWizardPage($manx, $vars);
$page->renderPage();

?>
