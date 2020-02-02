<?php

namespace Manx;

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class UrlWizardPage extends AdminPageBase
{
    /** @var IManxDatabase */
    private $_db;
    /** @var IUrlMetaData */
    private $_urlMeta;

    public function __construct(Container $config)
    {
        parent::__construct($config);
        $manx = $config['manx'];
        $this->_db = $manx->getDatabase();
        $this->_urlMeta = $config['urlMetaData'];
    }

    protected function getMenuType()
    {
        return MenuType::UrlWizard;
    }

    protected function postPage()
    {
        $companyId = $this->addCompany();
        $this->addSiteCompanyDirectory($companyId);
        $pubId = $this->addPublication($companyId);
        $this->addSupersession($pubId);
        $siteId = $this->addSite();
        $copyId = $this->addCopy($pubId, $siteId);
        if (array_key_exists('site_unknown_id', $this->_vars))
        {
            $siteUnknownId = $this->_vars['site_unknown_id'];
            $this->_db->setCopySiteUnknownDirId($copyId, $siteUnknownId);
            $this->_db->updateIgnoredUnknownSingleDir($siteUnknownId);
            $this->_db->removeSiteUnknownPathById($siteUnknownId);
        }
        $this->redirect(sprintf("details.php/%s,%s", $companyId, $pubId));
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

    private function addSiteCompanyDirectory($companyId)
    {
        $directory = $this->param('site_company_directory');
        if (strlen($directory) > 0)
        {
            $this->_db->addSiteDirectory($this->param('site_name'), $companyId, $directory, $this->param('site_company_parent_directory'));
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
                $this->param('pub_history_ph_pub_date'),
                $this->param('pub_history_ph_title'),
                $this->param('pub_history_ph_pub_type'),
                $this->param('pub_history_ph_alt_part'),
                $this->param('pub_history_ph_revision'),
                $this->param('pub_history_ph_keywords'),
                $this->param('pub_history_ph_notes'),
                $this->param('pub_history_ph_abstract'),
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
        return $this->_db->addCopy($pubId, $this->param('copy_format'),
            $siteId, $this->param('copy_url'), $this->param('copy_notes'),
            $this->param('copy_size'), '' /* MD5 */,
            $this->param('copy_credits'), $this->param('copy_amend_serial'));
    }

    protected function renderHeaderContent()
    {
        $this->renderLink("stylesheet", "text/css", "assets/UrlWizard.css");
        print <<<EOH
<script type="text/javascript" src="assets/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="assets/UrlWizard.js"></script>

EOH;
    }

    private static function pubOptions($pubs)
    {
        $options = [];
        foreach ($pubs as $pub)
        {
            $options[] = sprintf('<option value="%1$d">%2$s  %3$s</option>', $pub['pub_id'], $pub['ph_part'], $pub['ph_title']);
        }
        $options[] = '';
        return implode("\n", $options);
    }

    protected function renderBodyContent()
    {
        $idPresent = array_key_exists('id', $this->_vars);
        $urlPresent = array_key_exists('url', $this->_vars);
        $url = $urlPresent ? $this->_vars['url'] : '';
        $metaData = $urlPresent ? $this->_urlMeta->determineData($url)
            : [
                'format' => '',
                'site' => -1,
                'size' => 0,
                'pub_date' => '',
                'part' => '',
                'url' => $url,
                'mirror_url' => '',
                'company' => -1,
                'keywords' => '',
                'title' => ''
            ];
        $url = $metaData['url'];
        $mirrorUrl = $metaData['mirror_url'];
        $keywords = $metaData['keywords'];

        print <<<EOH
<h1>URL Wizard</h1>

<div id="form_container">
<form id="wizard" action="url-wizard.php" method="POST" name="f">


EOH;

        if ($idPresent)
        {
            $suId = $this->_vars['id'];
            print <<<EOH
<fieldset id="site_unknown_field" class="hidden">
<input type="hidden" id="site_unknown_id" name="site_unknown_id" value="$suId" />
</fieldset>


EOH;
        }

        $copyLink = $urlPresent ? sprintf(' href="%s"', $url) : '';
        $copyLinkClass = $urlPresent ? '' : 'hidden';
        $copyTextClass = $urlPresent ? 'hidden' : '';
        print <<<EOH
<fieldset id="copy_fields">
<legend id="copy_legend"><a id="copy_link"$copyLink class="$copyLinkClass">Copy</a><span id="copy_text" class="$copyTextClass">Copy</span></legend>
<ul>


EOH;
        $this->renderTextInput('Document URL', 'copy_url', [
            'size' => 60, 'maxlength' => 255, 'working' => true,
            'help' => 'The complete URL for the document.',
            'readonly' => $urlPresent, 'value' => $url
            ]);
        $this->renderTextInput('Mirror Document URL', 'copy_mirror_url', [
            'class' => strlen($mirrorUrl) == 0 ? 'hidden' : '', 'size' => 60, 'maxlength' => 255,
            'readonly' => true, 'value' => $mirrorUrl,
            'help' => 'Read-only.  The URL of a mirrored document as originally entered.']);
        $copyFormat = $metaData['format'];
        $this->renderTextInput('Format', 'copy_format', [
            'class' => strlen($copyFormat) == 0 ? 'hidden' : '', 'size' => 10, 'maxlength' => 10, 'value' => $copyFormat,
            'help' => 'The format of the document at the URL, i.e. PDF.']);
        $copySiteDisabled = $idPresent ? ' disabled="disabled"' : '';
        $copySiteClass = $idPresent ? '' : 'hidden';
        $siteId = $metaData['site']['site_id'];
        $copySiteHidden = $idPresent ? "<input type=\"hidden\" id=\"copy_site\" name=\"copy_site\" value=\"$siteId\" />\n" : '';
        print <<<EOH
<li id="copy_site_field" class="$copySiteClass">
<label for="copy_site">Site</label>
<select id="copy_site" name="copy_site"$copySiteDisabled>
<option value="-1">(New Site)</option>

EOH;

        foreach ($this->_db->getSites() as $site)
        {
            $selected = $siteId == $site['site_id'] ? ' selected="selected"' : '';
            printf("<option value=\"%d\"%s>%s</option>\n", $site['site_id'], $selected, $site['url']);
        }
        print <<<EOH
</select>
$copySiteHidden</li>


EOH;
        $this->renderTextInputMaxSize('Notes', 'copy_notes', 60, 200,
            'Notes about this copy of the publication.');
        $copySize = $metaData['size'];
        print <<<EOH
<input type="hidden" id="copy_size" name="copy_size" value="$copySize" />

<input type="hidden" id="copy_md5" name="copy_md5" value="" />


EOH;
        $this->renderTextInputMaxSize('Credits', 'copy_credits', 60, 200,
            'Credits for this copy, i.e. Scanned by legalize.');
        print <<<EOH
<input type="hidden" id="copy_amend_serial" name="copy_amend_serial" value="0" />

</ul>
</fieldset>

<fieldset id="site_company_field" class="hidden">
<input type="hidden" id="site_company_directory" name="site_company_directory" value="" />
<input type="hidden" id="site_company_parent_directory" name="site_company_parent_directory" value="" />
</fieldset>

<fieldset id="site_fields" class="hidden">
<legend id="site_legend">Site</legend>
<ul>


EOH;
        $this->renderTextInputMaxSize('Name', 'site_name', 60, 100,
            'The short, mnemonic name for the site.');
        $this->renderTextInputMaxSize('URL', 'site_url', 60, 200,
            'The main URL for the site.');
        $this->renderTextInputMaxSize('Description', 'site_description', 60, 200,
            'The description for the site as used on the About page.');
        $this->renderTextInputMaxSize('Copy Base', 'site_copy_base', 60, 200,
            'The base URL for documents on the site, which may be different'
                . ' from the site URL.');
        $companyClass = $idPresent ? '' : 'hidden';
        print <<<EOH
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

<fieldset id="company_fields" class="$companyClass">
<legend id="company_legend">Company</legend>
<ul>

<li id="company_id_field">
<label for="company_id">Company</label>
<select id="company_id" name="company_id">
<option value="-1">(New Company)</option>

EOH;
        foreach ($this->_db->getCompanyList() as $company)
        {
            $selected = $metaData['company'] == $company['id'] ? ' selected="selected"' : '';
            printf("<option value=\"%d\"%s>%s</option>\n", $company['id'], $selected, $company['name']);
        }
        print <<<EOH
</select>
</li>


EOH;
        $this->renderTextInput('Name', 'company_name',
            ['class' => 'hidden', 'size' => 50, 'maxlength' => 50,
            'help' => 'The full name of the company, i.e. Digital Equipment Corporation.  It will be used on the About page and in the company dropdown list on the search page.']);
        $this->renderTextInput('Short Name', 'company_short_name',
            ['class' => 'hidden', 'size' => 50, 'maxlength' => 50,
            'help' => 'A short name for the company, i.e. DEC.']);
        $this->renderTextInput('Sort Name', 'company_sort_name',
            ['class' => 'hidden', 'size' => 50, 'maxlength' => 50,
            'help' => 'A lower case sort key for the company, i.e. dec.']);
        $this->renderTextInput('Notes', 'company_notes',
            ['class' => 'hidden', 'size' => 60, 'maxlength' => 255,
            'help' => 'Notes for the company, i.e. terminal manufacturer']);
        $pubClass = $urlPresent ? '' : 'hidden';
        print <<<EOH
</ul>
</fieldset>

<fieldset id="publication_fields" class="$pubClass">
<legend id="publication_legend">Publication</legend>
<ul>


EOH;
        $this->renderTextInput('Search Keywords', 'pub_search_keywords', [
            'size' => 40, 'working' => true, 'value' => $keywords,
            'help' => 'Search keywords to locate a known publication.']);
        $publications = $urlPresent ? self::pubOptions($metaData['pubs']) : '';
        print <<<EOH
<li id="pub_pub_id_field">
<label for="pub_pub_id"><span id="pub_pub_id_label">Publication</span><a id="pub_pub_id_link" class="hidden">Publication</a></label>
<select id="pub_pub_id" name="pub_pub_id">
<option value="-1">(New Publication)</option>
$publications</select>
</li>


EOH;
        $this->renderTextInputMaxSize('Title', 'pub_history_ph_title', 60, 255,
            'The title of this document; exclude part numbers and publication dates.',
            ['value' => $metaData['title']]);
        $this->renderTextInputMaxSize('Revision', 'pub_history_ph_revision', 20, 20,
            'The revision number or letter of this publication, i.e. B');
        print <<<EOH
<li id="pub_history_ph_pub_type_field">
<label for="pub_history_ph_pub_type">Type</label>
<select id="pub_history_ph_pub_type" name="pub_history_ph_pub_type">
<option value="D" selected="selected">Document</option>
<option value="A">Amendment</option>
</select>
</li>


EOH;
        $this->renderTextInputMaxSize('Publication Date', 'pub_history_ph_pub_date', 10, 10,
            'The date of publication, if any, i.e. 1979-02.', ['value' => $metaData['pub_date']]);
        $this->renderTextInputMaxSize('Abstract', 'pub_history_ph_abstract', 60, 2048,
            'The abstract for the publication, if any.');
        $this->renderTextInput('Part #', 'pub_history_ph_part',
            ['maxlength' => 50, 'value' => $metaData['part'], 'help' => 'The part number for this publication, if any.']);
        $this->renderTextInput('Alternative Part #', 'pub_history_ph_alt_part',
            ['maxlength' => 50, 'help' => 'An alternate part number for the publication, if any.']);
        $this->renderTextInput('Keywords', 'pub_history_ph_keywords', [
            'maxlength' => 100, 'help' => 'A space separated list of keywords for this publication, i.e. terminal graphics.']);
        $this->renderTextInput('Notes', 'pub_history_ph_notes',
            ['maxlength' => 255, 'help' => 'Additional notes for this revision of the publication.']);
        $this->renderTextInput('Amends Publication', 'pub_history_ph_amend_pub',
            ['class' => 'hidden', 'maxlength' => 10, 'help' => 'Publication amended by this publication.']);
        $this->renderTextInput('Amendment Serial No.', 'pub_history_ph_amend_serial',
            ['class' => 'hidden', 'maxlength' => 10, 'help' => 'Serial number of this amendment.']);
        $supersedeClass = $urlPresent ? '' : 'hidden';
        print <<<EOH
</ul>
</fieldset>

<fieldset id="supersession_fields" class="$supersedeClass">
<legend id="supersession_legend">Supersession</legend>
<ul>


EOH;
        $this->renderTextInput('Search keywords', 'supersession_search_keywords', [
            'size' => 40, 'working' => true, 'value' => $keywords,
            'help' => 'Search keywords to locate publications superseded by or superseding this publication.']);
        print <<<EOH
<li id="supersession_old_pub_field">
<label for="supersession_old_pub"><span id="supersession_old_pub_label">Supersedes</span><a id="supersession_old_pub_link" class="hidden">Supersedes</a></label>
<select id="supersession_old_pub" name="supersession_old_pub">
<option value="-1">(None)</option>
$publications</select>
</li>


EOH;
        print <<<EOH
<li id="supersession_new_pub_field">
<label for="supersession_new_pub"><span id="supersession_new_pub_label">Superseded by</span><a id="supersession_new_pub_link" class="hidden">Superseded by</a></label>
<select id="supersession_new_pub" name="supersession_new_pub">
<option value="-1">(None)</option>
$publications</select>
</li>

</ul>
</fieldset>

<input type="submit" name="next" value="Next &gt;" />
</form>
</div>

EOH;
    }
}
