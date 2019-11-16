<?php

require_once 'test/TestAboutPage.php';
require_once 'test/TestAdminPageBase.php';
require_once 'test/TestBitSaversPage.php';
require_once 'test/TestBitSaversCleaner.php';
require_once 'test/TestBitSaversConfig.php';
require_once 'test/TestChiClassicCompConfig.php';
require_once 'test/TestChiClassicCompPage.php';
require_once 'test/TestCompanyPage.php';
require_once 'test/TestDetailsPage.php';
require_once 'test/TestHtmlFormatter.php';
require_once 'test/TestIngestionRobotUser.php';
require_once 'test/TestManx.php';
require_once 'test/TestManxDatabase.php';
require_once 'test/TestManxDatabaseUtils.php';
require_once 'test/TestPageBase.php';
require_once 'test/TestPublicationPage.php';
require_once 'test/TestRssPage.php';
require_once 'test/TestSearcher.php';
require_once 'test/TestUrlInfo.php';
require_once 'test/TestUrlMetaData.php';
require_once 'test/TestUrlMetaDataHelpers.php';
require_once 'test/TestUrlTransfer.php';
require_once 'test/TestUrlWizardPage.php';
require_once 'test/TestUrlWizardService.php';
require_once 'test/TestWhatsNewIndex.php';
require_once 'test/TestWhatsNewProcessor.php';

class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit\Framework\TestSuite('ManxTests');
        foreach ([
                'TestAboutPage',
                'TestAdminPageBase',
                'TestBitSaversCleaner',
                'TestBitSaversConfig',
                'TestBitSaversPage',
                'TestChiClassicCompConfig',
                'TestChiClassicCompPage',
                'TestCompanyPage',
                'TestDetailsPage',
                'TestHtmlFormatter',
                'TestIngestionRobotUser',
                'TestManx',
                'TestManxDatabase',
                'TestManxDatabaseUtils',
                'TestPageBase',
                'TestPublicationPage',
                'TestRssPage',
                'TestSearcher',
                'TestUrlInfo',
                'TestUrlMetaData',
                'TestUrlMetaDataHelpers',
                'TestUrlTransfer',
                'TestUrlWizardPage',
                'TestUrlWizardService',
                'TestWhatsNewIndex',
                'TestWhatsNewProcessor'
            ] as $name)
        {
            $suite->addTestSuite($name);
        }
        return $suite;
    }
}
