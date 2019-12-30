<?php

require_once __DIR__ . '/TestAboutPage.php';
require_once __DIR__ . '/TestAdminPageBase.php';
require_once __DIR__ . '/TestApiSiteUnknownDir.php';
require_once __DIR__ . '/TestApiSiteUnknownDirs.php';
require_once __DIR__ . '/TestApiSiteUnknownPaths.php';
require_once __DIR__ . '/TestBitSaversCleaner.php';
require_once __DIR__ . '/TestBitSaversConfig.php';
require_once __DIR__ . '/TestChiClassicCompConfig.php';
require_once __DIR__ . '/TestCompanyPage.php';
require_once __DIR__ . '/TestDetailsPage.php';
require_once __DIR__ . '/TestHtmlFormatter.php';
require_once __DIR__ . '/TestIngestionRobotUser.php';
require_once __DIR__ . '/TestManxDatabase.php';
require_once __DIR__ . '/TestManxDatabaseUtils.php';
require_once __DIR__ . '/TestManx.php';
require_once __DIR__ . '/TestPageBase.php';
require_once __DIR__ . '/TestPublicationPage.php';
require_once __DIR__ . '/TestRssPage.php';
require_once __DIR__ . '/TestSearcher.php';
require_once __DIR__ . '/TestSiteChecker.php';
require_once __DIR__ . '/TestUrlInfo.php';
require_once __DIR__ . '/TestUrlMetaDataHelpers.php';
require_once __DIR__ . '/TestUrlMetaData.php';
require_once __DIR__ . '/TestUrlTransfer.php';
require_once __DIR__ . '/TestUrlWizardPage.php';
require_once __DIR__ . '/TestUrlWizardService.php';
require_once __DIR__ . '/TestWhatsNewIndex.php';
require_once __DIR__ . '/TestWhatsNewPage.php';
require_once __DIR__ . '/TestWhatsNewProcessor.php';

class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit\Framework\TestSuite('ManxTests');
        foreach ([
                'TestAboutPage',
                'TestAdminPageBase',
                'TestApiSiteUnknownDir',
                'TestApiSiteUnknownDirs',
                'TestApiSiteUnknownPaths',
                'TestBitSaversCleaner',
                'TestBitSaversConfig',
                'TestChiClassicCompConfig',
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
                'TestSiteChecker',
                'TestUrlInfo',
                'TestUrlMetaData',
                'TestUrlMetaDataHelpers',
                'TestUrlTransfer',
                'TestUrlWizardPage',
                'TestUrlWizardService',
                'TestWhatsNewIndex',
                'TestWhatsNewPage',
                'TestWhatsNewProcessor'
            ] as $name)
        {
            $suite->addTestSuite($name);
        }
        return $suite;
    }
}
