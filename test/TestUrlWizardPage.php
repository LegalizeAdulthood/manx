<?php

require_once 'test/FakeManxDatabase.php';
require_once 'test/FakeManx.php';
require_once 'test/FakeUser.php';
require_once 'UrlWizardPage.php';

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
}

class TestUrlWizardPage extends PHPUnit_Framework_TestCase
{
	private $_manx;

	public function testConstruct()
	{
		$this->_manx = new FakeManx();
		$_SERVER['PATH_INFO'] = '';
		$vars = array();
		$page = new URLWizardPage($this->_manx, $vars);
		$this->assertTrue(is_object($page));
		$this->assertFalse(is_null($page));
	}

	public function testPostPage()
	{
		$this->_manx = new FakeManx();
		$this->_manx->addPublicationFakeResult = 19690;
		$db = new FakeManxDatabase();
		$this->_manx->getDatabaseFakeResult = $db;
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$vars = array(
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
			'pub_search_keywords' => 'Rev+B+4010+Maintenance+Manual',
			'pub_pub_id' => '-1',
			'pub_history_ph_title' => '4010+and+4010-1+Maintenance+Manual',
			'pub_history_ph_revision' => 'B',
			'pub_history_ph_pubtype' => 'D',  
			'pub_history_ph_pubdate' => '1976-04',
			'pub_history_ph_abstract' => '',   
			'pub_history_ph_part' => '070-1183-01',
			'pub_history_ph_match_part' => '',
			'pub_history_ph_sort_part' => '',
			'pub_history_ph_alt_part' => '',  
			'pub_history_ph_match_alt_part' => '',       
			'pub_history_ph_keywords' => 'terminal+graphics',
			'pub_history_ph_notes' => '',
			'pub_history_ph_class' => '',
			'pub_history_ph_amend_pub' => '',
			'pub_history_ph_amend_serial' => '',
			'supersession_search_keywords' => '4010+Maintenance+Manual',
			'supersession_old_pub' => '5634',
			'next' => 'Next+%3E');
		$page = new URLWizardPageTester($this->_manx, $vars);
		ob_start();

		$page->postPage();

		$output = ob_get_contents();
		$this->assertFalse($db->addCompanyCalled);
		$this->assertTrue($this->_manx->addPublicationCalled);
		$this->assertEquals($this->_manx->addPublicationLastTitle, urldecode($vars['pub_history_ph_title']));
		$this->assertEquals($this->_manx->addPublicationLastKeywords, urldecode($vars['pub_history_ph_keywords']));
		$this->assertTrue($db->addSupersessionCalled);
		$this->assertEquals(5634, $db->addSupersessionLastOldPub);
		$this->assertEquals(19690, $db->addSupersessionLastNewPub);
		$this->assertFalse($db->addSiteCalled);
		$this->assertTrue($db->addCopyCalled);
		$this->assertEquals($db->addCopyLastPubId, 19690);
		$this->assertEquals($db->addCopyLastFormat, urldecode($vars['copy_format']));
		$this->assertEquals($db->addCopyLastSiteId, urldecode($vars['copy_site']));
		$this->assertEquals($db->addCopyLastUrl, urldecode($vars['copy_url']));
		$this->assertEquals($db->addCopyLastNotes, urldecode($vars['copy_notes']));
		$this->assertEquals($db->addCopyLastSize, urldecode($vars['copy_size']));
		$this->assertEquals($db->addCopyLastMd5, urldecode($vars['copy_md5']));
		$this->assertEquals($db->addCopyLastCredits, urldecode($vars['copy_credits']));
		$this->assertEquals($db->addCopyLastAmendSerial, urldecode($vars['copy_amend_serial']));
		$this->assertTrue($page->redirectCalled);
	}

	public function testRenderPage()
	{
		$this->_manx = new FakeManx();
		$this->_manx->getUserFromSessionFakeResult = new FakeUser();
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$db = new FakeManxDatabase();
		$this->_manx->getDatabaseFakeResult = $db;
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
<legend id="copy_legend">Copy</legend>
<ul>
<li id="copy_url_field">
<label for="copy_url">Document URL</label>
<input type="text" id="copy_url" name="copy_url" size="60" maxlength="255" value="" />
</li>

<li id="copy_format_field" class="hidden">
<label for="copy_format">Format</label>
<input type="text" id="copy_format" name="copy_format" size="10" maxlength="10" value="" />
</li>

<li id="copy_site_field" class="hidden">
<label for="copy_site">Site</label>
<select id="copy_site" name="copy_site">
<option value="-1">(New Site)</option></select>
</li>

<li id="copy_notes_field">
<label for="copy_notes">Notes</label>
<input type="text" id="copy_notes" name="copy_notes" size="60" maxlength="200" value="" />
</li>

<input type="hidden" id="copy_size" name="copy_size" value="" />

<input type="hidden" id="copy_md5" name="copy_md5" value="" />

<li id="copy_credits_field">
<label for="copy_credits">Credits</label>
<input type="text" id="copy_credits" name="copy_credits" size="60" maxlength="200" value="" />
</li>

<input type="hidden" id="copy_amend_serial" name="copy_amend_serial" value="" />

</ul>
</fieldset>

<fieldset id="bitsavers_field" class="hidden">
<input type="hidden" id="bitsavers_new_directory" name="bitsavers_new_directory" value="false" />
<input type="hidden" id="bitsavers_directory" name="bitsavers_directory" value="" />
</fieldset>

<fieldset id="site_fields" class="hidden">
<legend id="site_legend">Site</legend>
<ul>

<li id="site_name_field">
<label for="site_name">Name</label>
<input type="text" id="site_name" name="site_name" maxlength="100" value="" />
</li>

<li id="site_url_field">
<label for="site_url">URL</label>
<input type="text" id="site_url" name="site_url" maxlength="200" value="" />
</li>

<li id="site_description_field">
<label for="site_description">Description</label>
<input type="text" id="site_description" name="site_description" maxlength="200" value="" />
</li>

<li id="site_copy_base_field">
<label for="site_copy_base">Copy Base</label>
<input type="text" id="site_copy_base" name="site_copy_base" maxlength="200" value="" />
</li>

<li>
<label for="site_low">Low Bandwidth?</label>
<input type="checkbox" id="site_low" name="site_low" value="" />
</li>

<li>
<label for="site_live">Live?</label>
<input type="checkbox" id="site_live" name="site_live" value="" />
</li>

</ul>
</fieldset>

<fieldset id="company_fields" class="hidden">
<legend id="company_legend">Company</legend>
<ul>

<li>
<label for="company_id">Company</label>
<select id="company_id" name="company_id">
<option value="-1">(New Company)</option>
</select>
</li>

<li id="company_name_field" class="hidden">
<label for="company_name">Name</label>
<input type="text" id="company_name" name="company_name" size="50" maxlength="50" value="" />
</li>

<li id="company_short_name_field" class="hidden">
<label for="company_short_name">Short Name</label>
<input type="text" id="company_short_name" name="company_short_name" size="50" maxlength="50" value="" />
</li>

<li id="company_sort_name_field" class="hidden">
<label for="company_sort_name">Sort Name</label>
<input type="text" id="company_sort_name" name="company_sort_name" size="50" maxlength="50" value="" />
</li>

<li id="company_notes_field" class="hidden">
<label for="company_notes">Notes</label>
<input type="text" id="company_notes" name="company_notes" size="60" maxlength="255" value="" />
</li>

</ul>
</fieldset>

<fieldset id="publication_fields" class="hidden">
<legend id="publication_legend">Publication</legend>
<ul>

<li id="pub_search_keywords_field">
<label for="pub_search_keywords">Search Keywords</label>
<input type="text" id="pub_search_keywords" name="pub_search_keywords" size="40" value="" />
</li>

<li>
<label for="pub_pub_id">Publication</label>
<select id="pub_pub_id" name="pub_pub_id">
<option value="-1">(New Publication)</option>
</select>
</li>

<li id="pub_history_ph_title_field">
<label for="pub_history_ph_title">Title</label>
<input type="text" id="pub_history_ph_title" name="pub_history_ph_title" size="60" maxlength="255" value="" />
</li>

<li id="pub_history_ph_revision_field">
<label for="pub_history_ph_revision">Revision</label>
<input type="text" id="pub_history_ph_revision" name="pub_history_ph_revision" size="20" maxlength="20" value="" />
</li>

<li id="pub_history_ph_pubtype_field">
<label for="pub_history_ph_pubtype">Type</label>
<select id="pub_history_ph_pubtype" name="pub_history_ph_pubtype">
<option value="D" selected="selected">Document</option>
<option value="A">Amendment</option>
</select>
</li>

<li id="pub_history_ph_pubdate_field">
<label for="pub_history_ph_pubdate">Publication Date</label>
<input type="text" id="pub_history_ph_pubdate" name="pub_history_ph_pubdate" size="10" maxlength="10" value="" />
</li>

<li id="pub_history_ph_abstract_field">
<label for="pub_history_ph_abstract">Abstract</label>
<input type="text" id="pub_history_ph_abstract" name="pub_history_ph_abstract" maxlength="255" value="" />
</li>

<li id="pub_history_ph_part_field">
<label for="pub_history_ph_part">Part #</label>
<input type="text" id="pub_history_ph_part" name="pub_history_ph_part" maxlength="50" value="" />
</li>

<li id="pub_history_ph_match_part_field">
<label for="pub_history_ph_match_part">Match Part #</label>
<input type="text" id="pub_history_ph_match_part" name="pub_history_ph_match_part" maxlength="50" value="" />
</li>

<li id="pub_history_ph_sort_part_field">
<label for="pub_history_ph_sort_part">Sort Part #</label>
<input type="text" id="pub_history_ph_sort_part" name="pub_history_ph_sort_part" maxlength="50" value="" />
</li>

<li id="pub_history_ph_alt_part_field">
<label for="pub_history_ph_alt_part">Alternative Part #</label>
<input type="text" id="pub_history_ph_alt_part" name="pub_history_ph_alt_part" maxlength="50" value="" />
</li>

<li id="pub_history_ph_match_alt_part_field">
<label for="pub_history_ph_match_alt_part">Match Alternative Part #</label>
<input type="text" id="pub_history_ph_match_alt_part" name="pub_history_ph_match_alt_part" maxlength="50" value="" />
</li>

<li id="pub_history_ph_keywords_field">
<label for="pub_history_ph_keywords">Keywords</label>
<input type="text" id="pub_history_ph_keywords" name="pub_history_ph_keywords" maxlength="100" value="" />
</li>

<li id="pub_history_ph_notes_field">
<label for="pub_history_ph_notes">Notes</label>
<input type="text" id="pub_history_ph_notes" name="pub_history_ph_notes" maxlength="255" value="" />
</li>

<li id="pub_history_ph_class_field">
<label for="pub_history_ph_class">Class</label>
<input type="text" id="pub_history_ph_class" name="pub_history_ph_class" maxlength="40" value="" />
</li>

<li id="pub_history_ph_amend_pub_field" class="hidden">
<label for="pub_history_ph_amend_pub">Amends Publication</label>
<input type="text" id="pub_history_ph_amend_pub" name="pub_history_ph_amend_pub" maxlength="10" value="" />
</li>

<li id="pub_history_ph_amend_serial_field" class="hidden">
<label for="pub_history_ph_amend_serial">Amendment Serial No.</label>
<input type="text" id="pub_history_ph_amend_serial" name="pub_history_ph_amend_serial" maxlength="10" value="" />
</li>

</ul>
</fieldset>

<fieldset id="supersession_fields" class="hidden">
<legend id="supersession_legend">Supersedes</legend>
<ul>

<li id="supersession_search_keywords_field">
<label for="supersession_search_keywords">Keywords</label>
<input type="text" id="supersession_search_keywords" name="supersession_search_keywords" size="40" value="" />
</li>

<li>
<label for="supersession_old_pub">Publication</label>
<select id="supersession_old_pub" name="supersession_old_pub">
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

?>
