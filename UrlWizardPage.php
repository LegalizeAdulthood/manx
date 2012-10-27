<?php

require_once 'Manx.php';
require_once 'AdminPageBase.php';
require_once 'UrlInfo.php';

class URLWizardPage extends AdminPageBase
{
	/** @var \IManxDatabase */
	private $_db;

	public function __construct($manx, $vars)
	{
		parent::__construct($manx, $vars);
		$this->_db = $manx->getDatabase();
	}

	protected function getMenuType()
	{
		return MenuType::UrlWizard;
	}

	protected function postPage()
	{
		$company = $this->addCompany();
		$this->addBitSaversDirectory($company);
		$pubId = $this->addPublication($company);
		$this->addSupersession($pubId);
		$siteId = $this->addSite();
		$this->addCopy($pubId, $siteId);
		$this->redirect(sprintf("details.php/%s,%s", $company, $pubId));
	}

	private function addCompany()
	{
		$company = $this->param('company_id');
		if ($company == -1)
		{
			$company = $this->_db->addCompany($this->param('company_name'),
				$this->param('company_short_name'), $this->param('company_sort_name'),
				true, $this->param('company_notes'));
		}
		return $company;
	}

	private function addBitSaversDirectory($companyId)
	{
		$directory = $this->param('bitsavers_directory');
		if (strlen($directory) > 0)
		{
			$this->_db->addBitSaversDirectory($companyId, $directory);
		}
	}

	private function addPublication($company)
	{
		$pubId = $this->param('pub_pub_id');
		if ($pubId == -1)
		{
			$languages = '+en';
			$pubId = $this->_manx->addPublication($this->_user, $company,
				$this->param('pub_history_ph_part'),
				$this->param('pub_history_ph_pubdate'),
				$this->param('pub_history_ph_title'),
				$this->param('pub_history_ph_pubtype'),
				$this->param('pub_history_ph_alt_part'),
				$this->param('pub_history_ph_revision'),
				$this->param('pub_history_ph_keywords'),
				$this->param('pub_history_ph_notes'),
				$languages);
		}
		return $pubId;
	}

	private function addSupersession($pubId)
	{
		$oldPub = $this->param('supersession_old_pub');
		$newPub = $this->param('supersession_new_pub');
		if ($oldPub != -1)
		{
			$this->_db->addSupersession($oldPub, $pubId);
		}
		else if ($newPub != -1)
		{
			$this->_db->addSupersession($pubId, $newPub);
		}
	}

	private static function ensureTrailingSlash($text)
	{
		return (substr(trim($text), -1) == '/') ?
			trim($text) : trim($text) . '/';
	}

	private static function yesNo($text)
	{
		return (strtolower($text) == 'y') ? 'Y' : 'N';
	}

	private function addSite()
	{
		$siteId = $this->param('copy_site');
		if ($siteId == -1)
		{
			$siteId = $this->_db->addSite($this->param('site_name'),
				UrlWizardPage::ensureTrailingSlash($this->param('site_url')),
				$this->param('site_description'),
				UrlWizardPage::ensureTrailingSlash($this->param('site_copy_base')),
				UrlWizardPage::yesNo($this->param('site_low')),
				UrlWizardPage::yesNo($this->param('site_live')));
		}
		return $siteId;
	}

	private function addCopy($pubId, $siteId)
	{
		$this->_db->addCopy($pubId, $this->param('copy_format'),
			$siteId, $this->param('copy_url'), $this->param('copy_notes'),
			$this->param('copy_size'), $this->getCopyMd5(),
			$this->param('copy_credits'), $this->param('copy_amend_serial'));
	}

	private function getCopyMd5()
	{
		$md5 = $this->param('copy_md5');
		if (!strlen($md5))
		{
			$url = $this->param('copy_mirror_url');
			if (!strlen($url))
			{
				$url = $this->param('copy_url');
			}
			$urlInfo = new UrlInfo($url);
			$result = $urlInfo->md5();
			if ($result !== false && $result[0] < 300)
			{
				$md5 = $result[1];
			}
		}
		return $md5;
	}

	protected function renderHeaderContent()
	{
		$this->renderLink("stylesheet", "text/css", "UrlWizard.css");
		print <<<EOH
<script type="text/javascript" src="jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="UrlWizard.js"></script>
EOH;
	}

	private function getAttribute($name, $options)
	{
		return array_key_exists($name, $options) ?
			sprintf(' %s="%s"', $name, $options[$name]) : '';
	}

	private function renderTextInput($label, $id, $options)
	{
		$className = $this->getAttribute('class', $options);
		$width = $this->getAttribute('size', $options);
		$maxLength = $this->getAttribute('maxlength', $options);
		$readOnly = array_key_exists('readonly', $options) ?
			' readonly="readonly"' : '';
		print <<<EOH
<li id="${id}_field"$className>
<label for="$id">$label</label>
<input type="text" id="$id" name="$id"$width$maxLength$readOnly value="" />

EOH;
		if (array_key_exists('help', $options))
		{
			$help = $options['help'];
			print <<<EOH
<img id="${id}_help_button" src="help.png" width="16" height="16" />
<div id="${id}_help" class="hidden">$help</div>
<div id="${id}_error" class="error hidden"></div>

EOH;
		}
		print <<<EOH
</li>


EOH;
	}

	private function renderTextInputMaxSize($label, $id, $size, $maxLength, $help)
	{
		$this->renderTextInput($label, $id,
			array('size' => $size, 'maxlength' => $maxLength,
			'help' => $help));
	}

	protected function renderBodyContent()
	{
		print <<<EOH
<h1>URL Wizard</h1>

<div id="form_container">
<form id="wizard" action="url-wizard.php" method="POST" name="f">

<fieldset id="copy_fields">
<legend id="copy_legend">Copy</legend>
<ul>


EOH;
		$this->renderTextInputMaxSize('Document URL', 'copy_url', 60, 255,
			'The complete URL for the document.');
		$this->renderTextInput('Mirror Document URL', 'copy_mirror_url',
			array('class' => 'hidden', 'size' => 60, 'maxlength' => 255,
				'readonly' => true,
				'help' => 'Read-only.  The URL of a mirrored document as originally entered.'));
		$this->renderTextInput('Format', 'copy_format',
			array('class' => 'hidden', 'size' => 10, 'maxlength' => 10,
				'help' => 'The format of the document at the URL, i.e. PDF.'));
		print <<<EOH
<li id="copy_site_field" class="hidden">
<label for="copy_site">Site</label>
<select id="copy_site" name="copy_site">
<option value="-1">(New Site)</option>
EOH;

		foreach ($this->_db->getSites() as $site)
		{
			printf("<option value=\"%d\">%s</option>\n", $site['siteid'], $site['url']);
		}
		print <<<EOH
</select>
</li>


EOH;
		$this->renderTextInputMaxSize('Notes', 'copy_notes', 60, 200,
			'Notes about this copy of the publication.');
		print <<<EOH
<input type="hidden" id="copy_size" name="copy_size" value="0" />

<input type="hidden" id="copy_md5" name="copy_md5" value="" />


EOH;
		$this->renderTextInputMaxSize('Credits', 'copy_credits', 60, 200,
			'Credits for this copy, i.e. Scanned by legalize.');
		print <<<EOH
<input type="hidden" id="copy_amend_serial" name="copy_amend_serial" value="0" />

</ul>
</fieldset>

<fieldset id="bitsavers_field" class="hidden">
<input type="hidden" id="bitsavers_new_directory" name="bitsavers_new_directory" value="false" />
<input type="hidden" id="bitsavers_directory" name="bitsavers_directory" value="" />
</fieldset>

<fieldset id="site_fields" class="hidden">
<legend id="site_legend">Site</legend>
<ul>


EOH;
		$this->renderTextInput('Name', 'site_name', array('maxlength' => 100,
			'help' => 'The short, mnemonic name for the site.'));
		$this->renderTextInput('URL', 'site_url', array('maxlength' => 200,
			'help' => 'The main URL for the site.'));
		$this->renderTextInput('Description', 'site_description',
			array('maxlength' => 200,
			'help' => 'The description for the site as used on the About page.'));
		$this->renderTextInput('Copy Base', 'site_copy_base', array('maxlength' => 200,
			'help' => 'The base URL for documents on the site, which may be different'
				. ' from the site URL.'));
		print <<<EOH
<li id="site_low_field">
<label for="site_low">Low Bandwidth?</label>
<input type="checkbox" id="site_low" name="site_low" value="Y" />
</li>

<li id="site_live_field">
<label for="site_live">Live?</label>
<input type="checkbox" id="site_live" name="site_live" value="Y" checked="checked" />
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

EOH;
		foreach ($this->_db->getCompanyList() as $company)
		{
			printf("<option value=\"%d\">%s</option>\n", $company['id'], $company['name']);
		}
		print <<<EOH
</select>
</li>


EOH;
		$this->renderTextInput('Name', 'company_name',
			array('class' => 'hidden', 'size' => 50, 'maxlength' => 50,
			'help' => 'The full name of the company, i.e. Digital Equipment Corporation.  It will be used on the About page and in the company dropdown list on the search page.'));
		$this->renderTextInput('Short Name', 'company_short_name',
			array('class' => 'hidden', 'size' => 50, 'maxlength' => 50,
			'help' => 'A short name for the company, i.e. DEC.'));
		$this->renderTextInput('Sort Name', 'company_sort_name',
			array('class' => 'hidden', 'size' => 50, 'maxlength' => 50,
			'help' => 'A lower case sort key for the company, i.e. dec.'));
		$this->renderTextInput('Notes', 'company_notes',
			array('class' => 'hidden', 'size' => 60, 'maxlength' => 255,
			'help' => 'Notes for the company, i.e. terminal manufacturer'));
		print <<<EOH
</ul>
</fieldset>

<fieldset id="publication_fields" class="hidden">
<legend id="publication_legend">Publication</legend>
<ul>


EOH;
		$this->renderTextInput('Search Keywords', 'pub_search_keywords',
			array('size' => 40, 'help' => 'Search keywords to locate a known publication.'));
		print <<<EOH
<li id="pub_pub_id_field">
<label for="pub_pub_id">Publication</label>
<select id="pub_pub_id" name="pub_pub_id">
<option value="-1">(New Publication)</option>
</select>
</li>


EOH;
		$this->renderTextInputMaxSize('Title', 'pub_history_ph_title', 60, 255,
			'The title of this document; exclude part numbers and publication dates.');
		$this->renderTextInputMaxSize('Revision', 'pub_history_ph_revision', 20, 20,
			'The revision number or letter of this publication, i.e. B');
		print <<<EOH
<li id="pub_history_ph_pubtype_field">
<label for="pub_history_ph_pubtype">Type</label>
<select id="pub_history_ph_pubtype" name="pub_history_ph_pubtype">
<option value="D" selected="selected">Document</option>
<option value="A">Amendment</option>
</select>
</li>


EOH;
		$this->renderTextInputMaxSize('Publication Date', 'pub_history_ph_pubdate', 10, 10,
			'The date of publication, if any, i.e. 1979-02.');
		$this->renderTextInput('Abstract', 'pub_history_ph_abstract',
			array('maxlength' => 255, 'help' => 'The abstract for the publication, if any.'));
		$this->renderTextInput('Part #', 'pub_history_ph_part',
			array('maxlength' => 50, 'help' => 'The part number for this publication, if any.'));
		$this->renderTextInput('Alternative Part #', 'pub_history_ph_alt_part',
			array('maxlength' => 50, 'help' => 'An alternate part number for the publication, if any.'));
		$this->renderTextInput('Keywords', 'pub_history_ph_keywords',
			array('maxlength' => 100, 'help' => 'A space separated list of keywords for this publication, i.e. terminal graphics.'));
		$this->renderTextInput('Notes', 'pub_history_ph_notes',
			array('maxlength' => 255, 'help' => 'Additional notes for this revision of the publication.'));
		$this->renderTextInput('Amends Publication', 'pub_history_ph_amend_pub',
			array('class' => 'hidden', 'maxlength' => 10, 'help' => 'Publication amended by this publication.'));
		$this->renderTextInput('Amendment Serial No.', 'pub_history_ph_amend_serial',
			array('class' => 'hidden', 'maxlength' => 10, 'help' => 'Serial number of this amendment.'));
		print <<<EOH
</ul>
</fieldset>

<fieldset id="supersession_fields" class="hidden">
<legend id="supersession_legend">Supersession</legend>
<ul>


EOH;
		$this->renderTextInput('Search keywords', 'supersession_search_keywords',
			array('size' => 40,
				'help' => 'Search keywords to locate publications superseded by or superceding this publication.'));
		print <<<EOH
<li id="supersession_old_pub_field">
<label for="supersession_old_pub">Supersedes</label>
<select id="supersession_old_pub" name="supersession_old_pub">
<option value="-1">(None)</option>
</select>
</li>


EOH;
		print <<<EOH
<li id="supersession_new_pub_field">
<label for="supersession_new_pub">Superseded by</label>
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
	}
}

?>
