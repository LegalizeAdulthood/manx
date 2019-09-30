<?php

require_once 'test/FakeManxDatabase.php';
require_once 'pages/UrlWizardPage.php';

class UrlWizardPageTester extends UrlWizardPage
{
    public function renderBodyContent()
    {
        parent::renderBodyContent();
    }

    protected function redirect($target)
    {
        $this->redirectCalled = true;
        $this->redirectLastTarget = $target;
    }
    public $redirectCalled, $redirectLastTarget;

    public function postPage()
    {
        parent::postPage();
    }

    protected function md5ForFile($url)
    {
        $this->md5ForFileCalled = true;
        $this->md5ForFileLastUrl = $url;
        return $this->md5ForFileFakeResult;
    }
    public $md5ForFileCalled, $md5ForFileLastUrl, $md5ForFileFakeResult;
}

class TestUrlWizardPage extends PHPUnit\Framework\TestCase
{
    private $_manx;

    public function testConstruct()
    {
        $this->_manx = $this->createMock(IManx::class);
        $_SERVER['PATH_INFO'] = '';
        $vars = array();

        $page = new URLWizardPage($this->_manx, $vars);

        $this->assertTrue(is_object($page));
        $this->assertFalse(is_null($page));
    }

    public function testDocumentAdded()
    {
        $db = new FakeManxDatabase();
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($db);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $part = '070-1183-01';
        $title = '4010 and 4010-1 Maintenance Manual';
        $keywords = 'terminal graphics';
        $abstract = 'This is the maintenance manual for Tektronix 4010 terminals.';
        $vars = array(
            'site_directory' => '',
            'copy_url' => 'http%3A%2F%2Fbitsavers.org%2Fpdf%2Ftektronix%2F401x%2F070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf',
            'copy_format' => 'PDF',
            'copy_site' => '3',
            'copy_notes' => '',
            'copy_size' => '',
            'copy_md5' => '',
            'copy_credits' => '',
            'copy_amend_serial' => '',
            'site_name' => '',
            'site_url' => '',
            'site_description' => '',
            'site_copy_base' => '',
            'company_id' => '5',
            'company_name' => '',
            'company_short_name' => '',
            'company_sort_name' => '',
            'company_notes' => '',
            'pub_search_keywords' => 'Rev B 4010 Maintenance Manual',
            'pub_pub_id' => '-1',
            'pub_history_ph_title' => $title,
            'pub_history_ph_revision' => 'B',
            'pub_history_ph_pub_type' => 'D',
            'pub_history_ph_pub_date' => '1976-04',
            'pub_history_ph_abstract' => $abstract,
            'pub_history_ph_part' => $part,
            'pub_history_ph_match_part' => '',
            'pub_history_ph_sort_part' => '',
            'pub_history_ph_alt_part' => '',
            'pub_history_ph_match_alt_part' => '',
            'pub_history_ph_keywords' => $keywords,
            'pub_history_ph_notes' => '',
            'pub_history_ph_class' => '',
            'pub_history_ph_amend_pub' => '',
            'pub_history_ph_amend_serial' => '',
            'supersession_search_keywords' => '4010 Maintenance Manual',
            'supersession_old_pub' => '5634',
            'next' => 'Next+%3E');
        $this->_manx->expects($this->once())->method('addPublication')
            ->with($this->anything(), $this->anything(), $this->equalTo($part), $this->anything(), $this->equalTo($title),
                $this->anything(), $this->anything(), $this->anything(), $this->equalTo($keywords), $this->anything(),
                $this->equalTo($abstract), $this->anything())
            ->willReturn(19690);
        $page = new URLWizardPageTester($this->_manx, $vars);
        $md5 = '01234567890123456789012345678901';
        $page->md5ForFileFakeResult = $md5;

        ob_start();
        $page->postPage();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertFalse($db->addCompanyCalled);
        $this->assertTrue($db->addSupersessionCalled);
        $this->assertEquals(5634, $db->addSupersessionLastOldPub);
        $this->assertEquals(19690, $db->addSupersessionLastNewPub);
        $this->assertFalse($db->addSiteCalled);
        $this->assertTrue($db->addCopyCalled);
        $this->assertEquals(19690, $db->addCopyLastPubId);
        $this->assertEquals($vars['copy_format'], $db->addCopyLastFormat);
        $this->assertEquals($vars['copy_site'], $db->addCopyLastSiteId);
        $this->assertEquals(rawurldecode($vars['copy_url']), $db->addCopyLastUrl);
        $this->assertEquals($vars['copy_notes'], $db->addCopyLastNotes);
        $this->assertEquals($vars['copy_size'], $db->addCopyLastSize);
        $this->assertEquals($md5, $db->addCopyLastMd5);
        $this->assertEquals($vars['copy_credits'], $db->addCopyLastCredits);
        $this->assertEquals($vars['copy_amend_serial'], $db->addCopyLastAmendSerial);
        $this->assertTrue($page->redirectCalled);
    }

    public function testNewBitSaversDirectoryAdded()
    {
        $this->_manx = $this->createMock(IManx::class);
        $db = new FakeManxDatabase();
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($db);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $part = '070-1183-01';
        $title = '4010 and 4010-1 Maintenance Manual';
        $keywords = 'terminal graphics';
        $abstract = 'This is the maintenance manual for Tektronix 4010 terminals.';
        $vars = array(
            'site_directory' => '',
            'copy_url' => 'http%3A%2F%2Fbitsavers.org%2Fpdf%2Ftektronix%2F401x%2F070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf',
            'copy_format' => 'PDF',
            'copy_site' => '3',
            'copy_notes' => '',
            'copy_size' => '',
            'copy_md5' => '',
            'copy_credits' => '',
            'copy_amend_serial' => '',
            'site_name' => '',
            'site_url' => '',
            'site_description' => '',
            'site_copy_base' => '',
            'company_id' => '5',
            'company_name' => '',
            'company_short_name' => '',
            'company_sort_name' => '',
            'company_notes' => '',
            'pub_search_keywords' => 'Rev B 4010 Maintenance Manual',
            'pub_pub_id' => '-1',
            'pub_history_ph_title' => $title,
            'pub_history_ph_revision' => 'B',
            'pub_history_ph_pub_type' => 'D',
            'pub_history_ph_pub_date' => '1976-04',
            'pub_history_ph_abstract' => $abstract,
            'pub_history_ph_part' => $part,
            'pub_history_ph_match_part' => '',
            'pub_history_ph_sort_part' => '',
            'pub_history_ph_alt_part' => '',
            'pub_history_ph_match_alt_part' => '',
            'pub_history_ph_keywords' => $keywords,
            'pub_history_ph_notes' => '',
            'pub_history_ph_class' => '',
            'pub_history_ph_amend_pub' => '',
            'pub_history_ph_amend_serial' => '',
            'supersession_search_keywords' => '4010 Maintenance Manual',
            'supersession_old_pub' => '5634',
            'next' => 'Next+%3E');
        $this->_manx->expects($this->once())->method('addPublication')
            ->with($this->anything(), $this->anything(), $this->equalTo($part), $this->anything(), $this->equalTo($title),
                $this->anything(), $this->anything(), $this->anything(), $this->equalTo($keywords), $this->anything(),
                $this->equalTo($abstract), $this->anything())
            ->willReturn(19690);
        $page = new URLWizardPageTester($this->_manx, $vars);
        $md5 = '01234567890123456789012345678901';
        $page->md5ForFileFakeResult = $md5;

        ob_start();
        $page->postPage();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertFalse($db->addCompanyCalled);
        $this->assertTrue($db->addSupersessionCalled);
        $this->assertEquals(5634, $db->addSupersessionLastOldPub);
        $this->assertEquals(19690, $db->addSupersessionLastNewPub);
        $this->assertFalse($db->addSiteCalled);
        $this->assertTrue($db->addCopyCalled);
        $this->assertEquals(19690, $db->addCopyLastPubId);
        $this->assertEquals($vars['copy_format'], $db->addCopyLastFormat);
        $this->assertEquals($vars['copy_site'], $db->addCopyLastSiteId);
        $this->assertEquals(rawurldecode($vars['copy_url']), $db->addCopyLastUrl);
        $this->assertEquals($vars['copy_notes'], $db->addCopyLastNotes);
        $this->assertEquals($vars['copy_size'], $db->addCopyLastSize);
        $this->assertEquals($md5, $db->addCopyLastMd5);
        $this->assertEquals($vars['copy_credits'], $db->addCopyLastCredits);
        $this->assertEquals($vars['copy_amend_serial'], $db->addCopyLastAmendSerial);
        $this->assertTrue($page->redirectCalled);
    }

    public function testNewChiClassicCompDirectoryAdded()
    {
        $this->_manx = $this->createMock(IManx::class);
        $db = new FakeManxDatabase();
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($db);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->_manx->addPublicationFakeResult = 19690;
        $vars = array(
            'site_company_directory' => 'DEC',
            'copy_url' => 'http%3A%2F%2Fchiclassiccomp.org%2Fdocs%2Fcontent%2Fcomputing%2FDEC%2FChicagoDECStore1.jpg',
            'copy_format' => 'JPEG',
            'copy_site' => '58',
            'copy_notes' => '',
            'copy_size' => '',
            'copy_md5' => '',
            'copy_credits' => '',
            'copy_amend_serial' => '',
            'site_name' => 'ChiClassicComp',
            'site_url' => '',
            'site_description' => '',
            'site_copy_base' => '',
            'company_id' => '5',
            'company_name' => '',
            'company_short_name' => '',
            'company_sort_name' => '',
            'company_notes' => '',
            'pub_search_keywords' => 'Chicago DEC Store1',
            'pub_pub_id' => '-1',
            'pub_history_ph_title' => 'Accessories & Supplies Center Chicago Brochure',
            'pub_history_ph_revision' => '',
            'pub_history_ph_pub_type' => 'D',
            'pub_history_ph_pub_date' => '1979',
            'pub_history_ph_abstract' => '',
            'pub_history_ph_part' => '',
            'pub_history_ph_match_part' => '',
            'pub_history_ph_sort_part' => '',
            'pub_history_ph_alt_part' => '',
            'pub_history_ph_match_alt_part' => '',
            'pub_history_ph_keywords' => '',
            'pub_history_ph_notes' => '',
            'pub_history_ph_class' => '',
            'pub_history_ph_amend_pub' => '',
            'pub_history_ph_amend_serial' => '',
            'supersession_search_keywords' => 'Chicago DEC Store1',
            'supersession_old_pub' => '-1',
            'supersession_new_pub' => '-1',
            'next' => 'Next+%3E');
        $page = new URLWizardPageTester($this->_manx, $vars);
        $md5 = '01234567890123456789012345678901';
        $page->md5ForFileFakeResult = $md5;

        ob_start();
        $page->postPage();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($db->addSiteDirectoryCalled);
        $this->assertEquals('ChiClassicComp', $db->addSiteDirectoryLastSiteName);
        $this->assertEquals('DEC', $db->addSiteDirectoryLastDirectory);
        $this->assertEquals('5', $db->addSiteDirectoryLastCompanyId);
    }

    public function testRenderPage()
    {
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $db = new FakeManxDatabase();
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($db);
        $vars = array();
        $page = new URLWizardPageTester($this->_manx, $vars);

        ob_start();
        $page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();

        $expected = <<<EOH
<h1>URL Wizard</h1>

<div id="form_container">
<form id="wizard" action="url-wizard.php" method="POST" name="f">

<fieldset id="copy_fields">
<legend id="copy_legend"><a id="copy_link" class="hidden">Copy</a><span id="copy_text">Copy</span></legend>
<ul>

<li id="copy_url_field">
<label for="copy_url">Document URL</label>
<input type="text" id="copy_url" name="copy_url" size="60" maxlength="255" value="" />
<img id="copy_url_help_button" src="assets/help.png" width="16" height="16" />
<span id="copy_url_working" class="hidden working">Working...</span>
<div id="copy_url_help" class="hidden">The complete URL for the document.</div>
<div id="copy_url_error" class="error hidden"></div>
</li>

<li id="copy_mirror_url_field" class="hidden">
<label for="copy_mirror_url">Mirror Document URL</label>
<input type="text" id="copy_mirror_url" name="copy_mirror_url" size="60" maxlength="255" readonly="readonly" value="" />
<img id="copy_mirror_url_help_button" src="assets/help.png" width="16" height="16" />
<div id="copy_mirror_url_help" class="hidden">Read-only.  The URL of a mirrored document as originally entered.</div>
<div id="copy_mirror_url_error" class="error hidden"></div>
</li>

<li id="copy_format_field" class="hidden">
<label for="copy_format">Format</label>
<input type="text" id="copy_format" name="copy_format" size="10" maxlength="10" value="" />
<img id="copy_format_help_button" src="assets/help.png" width="16" height="16" />
<div id="copy_format_help" class="hidden">The format of the document at the URL, i.e. PDF.</div>
<div id="copy_format_error" class="error hidden"></div>
</li>

<li id="copy_site_field" class="hidden">
<label for="copy_site">Site</label>
<select id="copy_site" name="copy_site">
<option value="-1">(New Site)</option></select>
</li>

<li id="copy_notes_field">
<label for="copy_notes">Notes</label>
<input type="text" id="copy_notes" name="copy_notes" size="60" maxlength="200" value="" />
<img id="copy_notes_help_button" src="assets/help.png" width="16" height="16" />
<div id="copy_notes_help" class="hidden">Notes about this copy of the publication.</div>
<div id="copy_notes_error" class="error hidden"></div>
</li>

<input type="hidden" id="copy_size" name="copy_size" value="0" />

<input type="hidden" id="copy_md5" name="copy_md5" value="" />

<li id="copy_credits_field">
<label for="copy_credits">Credits</label>
<input type="text" id="copy_credits" name="copy_credits" size="60" maxlength="200" value="" />
<img id="copy_credits_help_button" src="assets/help.png" width="16" height="16" />
<div id="copy_credits_help" class="hidden">Credits for this copy, i.e. Scanned by legalize.</div>
<div id="copy_credits_error" class="error hidden"></div>
</li>

<input type="hidden" id="copy_amend_serial" name="copy_amend_serial" value="0" />

</ul>
</fieldset>

<fieldset id="site_company_field" class="hidden">
<input type="hidden" id="site_company_directory" name="site_company_directory" value="" />
</fieldset>

<fieldset id="site_fields" class="hidden">
<legend id="site_legend">Site</legend>
<ul>

<li id="site_name_field">
<label for="site_name">Name</label>
<input type="text" id="site_name" name="site_name" size="60" maxlength="100" value="" />
<img id="site_name_help_button" src="assets/help.png" width="16" height="16" />
<div id="site_name_help" class="hidden">The short, mnemonic name for the site.</div>
<div id="site_name_error" class="error hidden"></div>
</li>

<li id="site_url_field">
<label for="site_url">URL</label>
<input type="text" id="site_url" name="site_url" size="60" maxlength="200" value="" />
<img id="site_url_help_button" src="assets/help.png" width="16" height="16" />
<div id="site_url_help" class="hidden">The main URL for the site.</div>
<div id="site_url_error" class="error hidden"></div>
</li>

<li id="site_description_field">
<label for="site_description">Description</label>
<input type="text" id="site_description" name="site_description" size="60" maxlength="200" value="" />
<img id="site_description_help_button" src="assets/help.png" width="16" height="16" />
<div id="site_description_help" class="hidden">The description for the site as used on the About page.</div>
<div id="site_description_error" class="error hidden"></div>
</li>

<li id="site_copy_base_field">
<label for="site_copy_base">Copy Base</label>
<input type="text" id="site_copy_base" name="site_copy_base" size="60" maxlength="200" value="" />
<img id="site_copy_base_help_button" src="assets/help.png" width="16" height="16" />
<div id="site_copy_base_help" class="hidden">The base URL for documents on the site, which may be different from the site URL.</div>
<div id="site_copy_base_error" class="error hidden"></div>
</li>

<li id="site_low_field">
<label for="site_low">Low Bandwidth?</label>
<input type="checkbox" id="site_low" name="site_low" value="Y" />
<img id="site_low_help_button" src="assets/help.png" width="16" height="16" />
<div id="site_low_help" class="hidden">If checked, the site is low bandwidth.</div>
<div id="site_low_error" class="error hidden"></div>
</li>

<li id="site_live_field">
<label for="site_live">Live?</label>
<input type="checkbox" id="site_live" name="site_live" value="Y" checked="checked" />
<img id="site_live_help_button" src="assets/help.png" width="16" height="16" />
<div id="site_live_help" class="hidden">If checked, the site is live.</div>
<div id="site_live_error" class="error hidden"></div>
</li>

</ul>
</fieldset>

<fieldset id="company_fields" class="hidden">
<legend id="company_legend">Company</legend>
<ul>

<li id="company_id_field">
<label for="company_id">Company</label>
<select id="company_id" name="company_id">
<option value="-1">(New Company)</option>
</select>
</li>

<li id="company_name_field" class="hidden">
<label for="company_name">Name</label>
<input type="text" id="company_name" name="company_name" size="50" maxlength="50" value="" />
<img id="company_name_help_button" src="assets/help.png" width="16" height="16" />
<div id="company_name_help" class="hidden">The full name of the company, i.e. Digital Equipment Corporation.  It will be used on the About page and in the company dropdown list on the search page.</div>
<div id="company_name_error" class="error hidden"></div>
</li>

<li id="company_short_name_field" class="hidden">
<label for="company_short_name">Short Name</label>
<input type="text" id="company_short_name" name="company_short_name" size="50" maxlength="50" value="" />
<img id="company_short_name_help_button" src="assets/help.png" width="16" height="16" />
<div id="company_short_name_help" class="hidden">A short name for the company, i.e. DEC.</div>
<div id="company_short_name_error" class="error hidden"></div>
</li>

<li id="company_sort_name_field" class="hidden">
<label for="company_sort_name">Sort Name</label>
<input type="text" id="company_sort_name" name="company_sort_name" size="50" maxlength="50" value="" />
<img id="company_sort_name_help_button" src="assets/help.png" width="16" height="16" />
<div id="company_sort_name_help" class="hidden">A lower case sort key for the company, i.e. dec.</div>
<div id="company_sort_name_error" class="error hidden"></div>
</li>

<li id="company_notes_field" class="hidden">
<label for="company_notes">Notes</label>
<input type="text" id="company_notes" name="company_notes" size="60" maxlength="255" value="" />
<img id="company_notes_help_button" src="assets/help.png" width="16" height="16" />
<div id="company_notes_help" class="hidden">Notes for the company, i.e. terminal manufacturer</div>
<div id="company_notes_error" class="error hidden"></div>
</li>

</ul>
</fieldset>

<fieldset id="publication_fields" class="hidden">
<legend id="publication_legend">Publication</legend>
<ul>

<li id="pub_search_keywords_field">
<label for="pub_search_keywords">Search Keywords</label>
<input type="text" id="pub_search_keywords" name="pub_search_keywords" size="40" value="" />
<img id="pub_search_keywords_help_button" src="assets/help.png" width="16" height="16" />
<span id="pub_search_keywords_working" class="hidden working">Working...</span>
<div id="pub_search_keywords_help" class="hidden">Search keywords to locate a known publication.</div>
<div id="pub_search_keywords_error" class="error hidden"></div>
</li>

<li id="pub_pub_id_field">
<label for="pub_pub_id"><span id="pub_pub_id_label">Publication</span><a id="pub_pub_id_link" class="hidden">Publication</a></label>
<select id="pub_pub_id" name="pub_pub_id">
<option value="-1">(New Publication)</option>
</select>
</li>

<li id="pub_history_ph_title_field">
<label for="pub_history_ph_title">Title</label>
<input type="text" id="pub_history_ph_title" name="pub_history_ph_title" size="60" maxlength="255" value="" />
<img id="pub_history_ph_title_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_title_help" class="hidden">The title of this document; exclude part numbers and publication dates.</div>
<div id="pub_history_ph_title_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_revision_field">
<label for="pub_history_ph_revision">Revision</label>
<input type="text" id="pub_history_ph_revision" name="pub_history_ph_revision" size="20" maxlength="20" value="" />
<img id="pub_history_ph_revision_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_revision_help" class="hidden">The revision number or letter of this publication, i.e. B</div>
<div id="pub_history_ph_revision_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_pub_type_field">
<label for="pub_history_ph_pub_type">Type</label>
<select id="pub_history_ph_pub_type" name="pub_history_ph_pub_type">
<option value="D" selected="selected">Document</option>
<option value="A">Amendment</option>
</select>
</li>

<li id="pub_history_ph_pub_date_field">
<label for="pub_history_ph_pub_date">Publication Date</label>
<input type="text" id="pub_history_ph_pub_date" name="pub_history_ph_pub_date" size="10" maxlength="10" value="" />
<img id="pub_history_ph_pub_date_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_pub_date_help" class="hidden">The date of publication, if any, i.e. 1979-02.</div>
<div id="pub_history_ph_pub_date_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_abstract_field">
<label for="pub_history_ph_abstract">Abstract</label>
<input type="text" id="pub_history_ph_abstract" name="pub_history_ph_abstract" size="60" maxlength="2048" value="" />
<img id="pub_history_ph_abstract_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_abstract_help" class="hidden">The abstract for the publication, if any.</div>
<div id="pub_history_ph_abstract_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_part_field">
<label for="pub_history_ph_part">Part #</label>
<input type="text" id="pub_history_ph_part" name="pub_history_ph_part" maxlength="50" value="" />
<img id="pub_history_ph_part_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_part_help" class="hidden">The part number for this publication, if any.</div>
<div id="pub_history_ph_part_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_alt_part_field">
<label for="pub_history_ph_alt_part">Alternative Part #</label>
<input type="text" id="pub_history_ph_alt_part" name="pub_history_ph_alt_part" maxlength="50" value="" />
<img id="pub_history_ph_alt_part_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_alt_part_help" class="hidden">An alternate part number for the publication, if any.</div>
<div id="pub_history_ph_alt_part_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_keywords_field">
<label for="pub_history_ph_keywords">Keywords</label>
<input type="text" id="pub_history_ph_keywords" name="pub_history_ph_keywords" maxlength="100" value="" />
<img id="pub_history_ph_keywords_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_keywords_help" class="hidden">A space separated list of keywords for this publication, i.e. terminal graphics.</div>
<div id="pub_history_ph_keywords_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_notes_field">
<label for="pub_history_ph_notes">Notes</label>
<input type="text" id="pub_history_ph_notes" name="pub_history_ph_notes" maxlength="255" value="" />
<img id="pub_history_ph_notes_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_notes_help" class="hidden">Additional notes for this revision of the publication.</div>
<div id="pub_history_ph_notes_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_amend_pub_field" class="hidden">
<label for="pub_history_ph_amend_pub">Amends Publication</label>
<input type="text" id="pub_history_ph_amend_pub" name="pub_history_ph_amend_pub" maxlength="10" value="" />
<img id="pub_history_ph_amend_pub_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_amend_pub_help" class="hidden">Publication amended by this publication.</div>
<div id="pub_history_ph_amend_pub_error" class="error hidden"></div>
</li>

<li id="pub_history_ph_amend_serial_field" class="hidden">
<label for="pub_history_ph_amend_serial">Amendment Serial No.</label>
<input type="text" id="pub_history_ph_amend_serial" name="pub_history_ph_amend_serial" maxlength="10" value="" />
<img id="pub_history_ph_amend_serial_help_button" src="assets/help.png" width="16" height="16" />
<div id="pub_history_ph_amend_serial_help" class="hidden">Serial number of this amendment.</div>
<div id="pub_history_ph_amend_serial_error" class="error hidden"></div>
</li>

</ul>
</fieldset>

<fieldset id="supersession_fields" class="hidden">
<legend id="supersession_legend">Supersession</legend>
<ul>

<li id="supersession_search_keywords_field">
<label for="supersession_search_keywords">Search keywords</label>
<input type="text" id="supersession_search_keywords" name="supersession_search_keywords" size="40" value="" />
<img id="supersession_search_keywords_help_button" src="assets/help.png" width="16" height="16" />
<span id="supersession_search_keywords_working" class="hidden working">Working...</span>
<div id="supersession_search_keywords_help" class="hidden">Search keywords to locate publications superseded by or superseding this publication.</div>
<div id="supersession_search_keywords_error" class="error hidden"></div>
</li>

<li id="supersession_old_pub_field">
<label for="supersession_old_pub"><span id="supersession_old_pub_label">Supersedes</span><a id="supersession_old_pub_link" class="hidden">Supersedes</a></label>
<select id="supersession_old_pub" name="supersession_old_pub">
<option value="-1">(None)</option>
</select>
</li>

<li id="supersession_new_pub_field">
<label for="supersession_new_pub"><span id="supersession_new_pub_label">Superseded by</span><a id="supersession_new_pub_link" class="hidden">Superseded by</a></label>
<select id="supersession_new_pub" name="supersession_new_pub">
<option value="-1">(None)</option>
</select>
</li>

</ul>
</fieldset>

<input type="submit" name="next" value="Next &gt;" />
</form>
</div>

EOH;
        $this->assertEquals($expected, $output);
    }
}
