<?php

require_once 'test/TestAboutPage.php';
require_once 'test/TestAdminPageBase.php';
require_once 'test/TestBitSaversPage.php';
require_once 'test/TestBitSaversCleaner.php';
require_once 'test/TestDetailsPage.php';
require_once 'test/TestHtmlFormatter.php';
require_once 'test/TestManxDatabase.php';
require_once 'test/TestPageBase.php';
require_once 'test/TestRssPage.php';
require_once 'test/TestSearcher.php';
require_once 'test/TestUrlInfo.php';
require_once 'test/TestUrlTransfer.php';
require_once 'test/TestUrlWizardPage.php';
require_once 'test/TestUrlWizardService.php';

class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('ManxTests');
        foreach (array('TestAboutPage', 'TestDetailsPage', 'TestHtmlFormatter',
             'TestManxDatabase', 'TestPageBase', 'TestRssPage', 'TestSearcher',
             'TestUrlWizardPage', 'TestUrlWizardService', 'TestAdminPageBase',
             'TestUrlInfo', 'TestUrlTransfer', 'TestBitSaversPage',
             'TestBitSaversCleaner') as $name)
        {
            $suite->addTestSuite($name);
        }
        return $suite;
    }
}
