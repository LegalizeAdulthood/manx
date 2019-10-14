<?php

require_once 'test/TestAboutPage.php';
require_once 'test/TestAdminPageBase.php';
require_once 'test/TestBitSaversPage.php';
require_once 'test/TestBitSaversCleaner.php';
require_once 'test/TestChiClassicCompPage.php';
require_once 'test/TestCompanyPage.php';
require_once 'test/TestDetailsPage.php';
require_once 'test/TestHtmlFormatter.php';
require_once 'test/TestManxDatabase.php';
require_once 'test/TestManxDatabaseUtils.php';
require_once 'test/TestPageBase.php';
require_once 'test/TestRssPage.php';
require_once 'test/TestSearcher.php';
require_once 'test/TestUrlInfo.php';
require_once 'test/TestUrlTransfer.php';
require_once 'test/TestUrlWizardPage.php';
require_once 'test/TestUrlWizardService.php';
require_once 'test/TestUrlWizardServiceProcessRequest.php';

class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit\Framework\TestSuite('ManxTests');
        foreach (array(
            'TestAboutPage',
            'TestAdminPageBase',
            'TestBitSaversCleaner',
            'TestBitSaversPage',
            'TestChiClassicCompPage',
            'TestCompanyPage',
            'TestDetailsPage',
            'TestHtmlFormatter',
            'TestManxDatabase',
            'TestManxDatabaseUtils',
            'TestPageBase',
            'TestRssPage',
            'TestSearcher',
            'TestUrlInfo',
            'TestUrlTransfer',
            'TestUrlWizardPage',
            'TestUrlWizardService',
            'TestUrlWizardServiceProcessRequest') as $name)
        {
            $suite->addTestSuite($name);
        }
        return $suite;
    }
}
