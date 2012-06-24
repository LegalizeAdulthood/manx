<?php

require_once 'Manx.php';
require_once 'AdminPageBase.php';

class URLWizardPage extends AdminPageBase
{
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

	private function addSite()
	{
		$siteId = $this->param('copy_site');
		if ($siteId == -1)
		{
			$siteId = $this->_db->addSite($this->param('site_name'),
				$this->param('site_url'), $this->param('site_description'),
				$this->param('site_copy_base'), $this->param('site_low'),
				$this->param('site_live'));
		}
		return $siteId;
	}

	private function addCopy($pubId, $siteId)
	{
		$this->_db->addCopy($pubId, $this->param('copy_format'),
			$siteId, $this->param('copy_url'), $this->param('copy_notes'),
			$this->param('copy_size'), $this->param('copy_md5'),
			$this->param('copy_credits'), $this->param('copy_amend_serial'));
	}

    protected function renderHeaderContent()
	{
		$this->renderLink("stylesheet", "text/css", "UrlWizard.css");
		print <<<EOH
<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
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
		print <<<EOH
<li id="${id}_field"$className>
<label for="$id">$label</label>
<input type="text" id="$id" name="$id"$width$maxLength value="" />
</li>


EOH;
	}

	private function renderTextInputMaxSize($label, $id, $size, $maxLength)
	{
		$this->renderTextInput($label, $id, array('size' => $size, 'maxlength' => $maxLength));
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
		$this->renderTextInputMaxSize('Document URL', 'copy_url', 60, 255);
		$this->renderTextInput('Format', 'copy_format', array('class' => 'hidden', 'size' => 10, 'maxlength' => 10));
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
		$this->renderTextInputMaxSize('Notes', 'copy_notes', 60, 200);
		print <<<EOH
<input type="hidden" id="copy_size" name="copy_size" value="" />

<input type="hidden" id="copy_md5" name="copy_md5" value="" />


EOH;
		$this->renderTextInputMaxSize('Credits', 'copy_credits', 60, 200);
		print <<<EOH
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


EOH;
		$this->renderTextInput('Name', 'site_name', array('maxlength' => 100));
		$this->renderTextInput('URL', 'site_url', array('maxlength' => 200));
		$this->renderTextInput('Description', 'site_description', array('maxlength' => 200));
		$this->renderTextInput('Copy Base', 'site_copy_base', array('maxlength' => 200));
		print <<<EOH
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

EOH;
		foreach ($this->_db->getCompanyList() as $company)
		{
			printf("<option value=\"%d\">%s</option>\n", $company['id'], $company['name']);
		}
		print <<<EOH
</select>
</li>


EOH;
		$this->renderTextInput('Name', 'company_name', array('class' => 'hidden', 'size' => 50, 'maxlength' => 50));
		$this->renderTextInput('Short Name', 'company_short_name', array('class' => 'hidden', 'size' => 50, 'maxlength' => 50));
		$this->renderTextInput('Sort Name', 'company_sort_name', array('class' => 'hidden', 'size' => 50, 'maxlength' => 50));
		$this->renderTextInput('Notes', 'company_notes', array('class' => 'hidden', 'size' => 60, 'maxlength' => 255));
		print <<<EOH
</ul>
</fieldset>

<fieldset id="publication_fields" class="hidden">
<legend id="publication_legend">Publication</legend>
<ul>


EOH;
		$this->renderTextInput('Search Keywords', 'pub_search_keywords', array('size' => 40));
		print <<<EOH
<li>
<label for="pub_pub_id">Publication</label>
<select id="pub_pub_id" name="pub_pub_id">
<option value="-1">(New Publication)</option>
</select>
</li>


EOH;
		$this->renderTextInputMaxSize('Title', 'pub_history_ph_title', 60, 255);
		$this->renderTextInputMaxSize('Revision', 'pub_history_ph_revision', 20, 20);
		print <<<EOH
<li id="pub_history_ph_pubtype_field">
<label for="pub_history_ph_pubtype">Type</label>
<select id="pub_history_ph_pubtype" name="pub_history_ph_pubtype">
<option value="D" selected="selected">Document</option>
<option value="A">Amendment</option>
</select>
</li>


EOH;
		$this->renderTextInputMaxSize('Publication Date', 'pub_history_ph_pubdate', 10, 10);
		$this->renderTextInput('Abstract', 'pub_history_ph_abstract', array('maxlength' => 255));
		$this->renderTextInput('Part #', 'pub_history_ph_part', array('maxlength' => 50));
		$this->renderTextInput('Match Part #', 'pub_history_ph_match_part', array('maxlength' => 50));
		$this->renderTextInput('Sort Part #', 'pub_history_ph_sort_part', array('maxlength' => 50));
		$this->renderTextInput('Alternative Part #', 'pub_history_ph_alt_part', array('maxlength' => 50));
		$this->renderTextInput('Match Alternative Part #', 'pub_history_ph_match_alt_part', array('maxlength' => 50));
		$this->renderTextInput('Keywords', 'pub_history_ph_keywords', array('maxlength' => 100));
		$this->renderTextInput('Notes', 'pub_history_ph_notes', array('maxlength' => 255));
		$this->renderTextInput('Class', 'pub_history_ph_class', array('maxlength' => 40));
		$this->renderTextInput('Amends Publication', 'pub_history_ph_amend_pub',
			array('class' => 'hidden', 'maxlength' => 10));
		$this->renderTextInput('Amendment Serial No.', 'pub_history_ph_amend_serial',
			array('class' => 'hidden', 'maxlength' => 10));
		print <<<EOH
</ul>
</fieldset>

<fieldset id="supersession_fields" class="hidden">
<legend id="supersession_legend">Supersession</legend>
<ul>


EOH;
		$this->renderTextInput('Search keywords', 'supersession_search_keywords', array('size' => 40));
		print <<<EOH
<li>
<label for="supersession_old_pub">Supersedes</label>
<select id="supersession_old_pub" name="supersession_old_pub">
<option value="-1">(None)</option>
</select>
</li>


EOH;
		print <<<EOH
<li>
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
