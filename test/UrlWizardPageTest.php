<?php

use Pimple\Container;

require_once __DIR__ . '/../vendor/autoload.php';

class UrlWizardPageTester extends Manx\UrlWizardPage
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

    public function renderHeaderContent()
    {
        parent::renderHeaderContent();
    }
}

class UrlWizardPageTest extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;
    /** @var Manx\IManx */
    private $_manx;
    /** @var Manx\IManxDatabase */
    private $_db;
    /** @var array */
    private $_vars;
    /** @vars Manx\IUrlMetaData */
    private $_urlMeta;
    /** @var UrlWizardPage */
    private $_page;

    protected function setUp()
    {
        $_SERVER['PATH_INFO'] = '';
        $manx = $this->createMock(Manx\IManx::class);

        $config = new Container();
        $config['manx'] = $manx;
        $this->_vars = [];
        $config['vars'] = $this->_vars;
        $this->_urlMeta = $this->createMock(Manx\IUrlMetaData::class);
        $config['urlMetaData'] = $this->_urlMeta;

        $this->_manx = $manx;
        $this->_config = $config;
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_page = new UrlWizardPageTester($this->_config);
    }

    private function createPage($vars = [])
    {
        $this->_vars = $vars;
        $this->_config['vars'] = $vars;
        $this->_page = new UrlWizardPageTester($this->_config);
    }

    public function testConstruct()
    {
        $this->assertTrue(is_object($this->_page));
        $this->assertFalse(is_null($this->_page));
    }

    public function testDocumentAdded()
    {
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($this->_db);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $part = '070-1183-01';
        $title = '4010 and 4010-1 Maintenance Manual';
        $keywords = 'terminal graphics';
        $abstract = 'This is the maintenance manual for Tektronix 4010 terminals.';
        $copyId = 6066;
        $siteUnknownId = 7077;
        $vars = array_merge(
            self::copyData('http://bitsavers.org/pdf/tektronix/401x/070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf', 'PDF', '3'),
            self::siteData(),
            self::companyData('5'),
            self::pubHistoryData($title, 'D', '1976-04', 'B', $abstract, $part, $keywords),
            [
                'site_unknown_id' => $siteUnknownId,
                'site_company_directory' => '',
                'site_company_parent_directory' => '',
                'pub_search_keywords' => 'Rev B 4010 Maintenance Manual',
                'pub_pub_id' => '-1',
                'supersession_search_keywords' => '4010 Maintenance Manual',
                'supersession_old_pub' => '5634',
                'next' => 'Next+%3E'
            ]);
        $this->_config['vars'] = $vars;
        $this->_manx->expects($this->once())->method('addPublication')
            ->with($this->anything(), $this->anything(), $part, $this->anything(), $title,
                $this->anything(), $this->anything(), $this->anything(), $keywords, $this->anything(),
                $abstract, $this->anything())
            ->willReturn(19690);
        $page = new UrlWizardPageTester($this->_config);
        $this->_db->expects($this->never())->method('addCompany');
        $this->_db->expects($this->once())->method('addSupersession')->with(5634, 19690);
        $this->_db->expects($this->never())->method('addSite');
        $this->_db->expects($this->once())->method('addCopy')
            ->with(
                19690, $vars['copy_format'], $vars['copy_site'], rawurldecode($vars['copy_url']),
                $vars['copy_notes'], $vars['copy_size'], '', $vars['copy_credits'],
                $vars['copy_amend_serial'])
            ->willReturn($copyId);
        $this->_db->expects($this->once())->method('setCopySiteUnknownDirId')->with($copyId, $siteUnknownId);
        $this->_db->expects($this->once())->method('removeSiteUnknownPathById')->with($siteUnknownId);

        $page->postPage();

        $this->assertTrue($page->redirectCalled);
    }

    public function testNewBitSaversDirectoryAdded()
    {
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($this->_db);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $part = '070-1183-01';
        $title = '4010 and 4010-1 Maintenance Manual';
        $keywords = 'terminal graphics';
        $abstract = 'This is the maintenance manual for Tektronix 4010 terminals.';
        $vars = array_merge(
            self::copyData('http://bitsavers.org/pdf/tektronix/401x/070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf', 'PDF', '3'),
            self::siteData(),
            self::companyData('5'),
            self::pubHistoryData($title, 'D', '1976-04', 'B', $abstract, $part, $keywords),
            [
                'site_company_directory' => '',
                'site_company_parent_directory' => '',
                'pub_search_keywords' => 'Rev B 4010 Maintenance Manual',
                'pub_pub_id' => '-1',
                'supersession_search_keywords' => '4010 Maintenance Manual',
                'supersession_old_pub' => '5634',
                'next' => 'Next+%3E'
            ]);
        $this->_config['vars'] = $vars;
        $this->_manx->expects($this->once())->method('addPublication')
            ->with($this->anything(), $this->anything(), $part, $this->anything(), $title,
                $this->anything(), $this->anything(), $this->anything(), $keywords, $this->anything(),
                $abstract, $this->anything())
            ->willReturn(19690);
        $page = new UrlWizardPageTester($this->_config);
        $this->_db->expects($this->never())->method('addCompany');
        $this->_db->expects($this->once())->method('addSupersession')->with(5634, 19690);
        $this->_db->expects($this->once())->method('addCopy')
            ->with(
                19690, $vars['copy_format'], $vars['copy_site'], rawurldecode($vars['copy_url']),
                $vars['copy_notes'], $vars['copy_size'], '', $vars['copy_credits'],
                $vars['copy_amend_serial']
            );

        $page->postPage();

        $this->assertTrue($page->redirectCalled);
    }

    public function testNewVtdaDirectoryAdded()
    {
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($this->_db);
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $vars = array_merge(
            self::copyData('http://vtda.org/docs/computing/DEC/ChicagoDECStore1.jpg', 'JPEG', '58'),
            self::siteData('VTDA'),
            self::companyData('5'),
            self::pubHistoryData('Accessories & Supplies Center Chicago Brochure', 'D', '1979'),
            [
                'site_company_directory' => 'DEC',
                'site_company_parent_directory' => '',
                'pub_search_keywords' => 'Chicago DEC Store1',
                'pub_pub_id' => '-1',
                'supersession_search_keywords' => 'Chicago DEC Store1',
                'supersession_old_pub' => '-1',
                'supersession_new_pub' => '-1',
                'next' => 'Next+%3E'
            ]);
        $this->_config['vars'] = $vars;
        $page = new UrlWizardPageTester($this->_config);
        $this->_db->expects($this->once())->method('addSiteDirectory')
            ->with('VTDA', '5', 'DEC');

        $page->postPage();
    }

    public function testRenderHeaderContent()
    {
        $this->createPage();
        $expected = <<<EOH
<link rel="stylesheet" type="text/css" href="assets/UrlWizard.css" />
<script type="text/javascript" src="assets/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="assets/UrlWizard.js"></script>

EOH;

        $this->_page->renderHeaderContent();

        $this->expectOutputString($expected);
    }

    public function testRenderPageNoParams()
    {
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->_db->expects($this->once())->method('getSites')->willReturn([]);
        $this->_db->expects($this->once())->method('getCompanyList')->willReturn([]);
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($this->_db);
        $vars = [];
        $this->_config['vars'] = $vars;
        $page = new UrlWizardPageTester($this->_config);

        $page->renderBodyContent();

        $expected = self::expectedBodyContent($vars);
        $this->expectOutputString($expected);
    }

    public function testRenderPageParams()
    {
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $siteId = 3;
        $sites = \Manx\Test\RowFactory::createResultRowsForColumns([ 'site_id', 'name', 'url', 'description', 'copy_base', 'low', 'live', 'display_order' ],
            [
                [ $siteId, 'bitsavers', 'http://bitsavers.org', '', 'http://bitsavers.org/pdf/', 'N', 'Y', 0 ],
                [ 58, 'VTDA', 'http://vtda.org', '', 'http://vtda.org/docs/', 'N', 'Y', 0 ]
            ]);
        $this->_db->expects($this->once())->method('getSites')->willReturn($sites);
        $companyId = 22;
        $companies = \Manx\Test\RowFactory::createResultRowsForColumns([ 'id', 'name' ],
            [
                [ $companyId, 'DEC' ],
                [ 23, 'IBM' ]
            ]);
        $pubs = \Manx\Test\RowFactory::createResultRowsForColumns([
                'pub_id', 'ph_part', 'ph_title', 'pub_has_online_copies',
                'ph_abstract', 'pub_has_toc', 'pub_superseded', 'ph_pub_date',
                'ph_revision', 'ph_company', 'ph_alt_part', 'ph_pub_type'
            ],
            [
                [ 2211, 'TK-001', "DIBOL User's Guide", 1, '', 0, 0, '1978-01', '', $companyId, '', 'D' ],
                [ 2212, 'TK-002', "DIBOL Programmer's Guide", 1, '', 0, 0, '1978-01', '', $companyId, '', 'D' ],
            ]);
        $this->_db->expects($this->once())->method('getCompanyList')->willReturn($companies);
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($this->_db);
        $siteUnknownId = 5522;
        $url = 'http://bitsavers.trailing-edge.com/pdf/dec/dibol/AA-BI77A-TK_DIBOL_for_Beginners_Apr1984.pdf';
        $vars = ['id' => $siteUnknownId, 'url' => $url];
        $this->_config['vars'] = $vars;
        $size = 10204;
        $part = 'AA-BI77A-TK';
        $title = 'DIBOL for Beginners';
        $pubDate = '1984-04';
        $metaData = [
            'url' => 'http://bitsavers.org/pdf/dec/dibol/AA-BI77A-TK_DIBOL_for_Beginners_Apr1984.pdf',
            'mirror_url' => $url,
            'size' => $size,
            'valid' => true,
            'site' => [
                'site_id' => $siteId,
                'name' => 'bitsavers',
                'url' => 'http://bitsavers.org',
                'description' => '',
                'copy_base' => 'http://bitsavers.org/pdf/',
                'low' => 'N',
                'live' => 'Y',
                'display_order' => 1
            ],
            'company' => $companyId,
            'part' => $part,
            'pub_date' => $pubDate,
            'title' => $title,
            'format' => 'PDF',
            'site_company_directory' => 'dec',
            'site_company_parent_directory' => '',
            'pubs' => $pubs,
            'keywords' => $part . ' ' . $title
        ];
        $this->_urlMeta->expects($this->once())->method('determineData')->with($url)->willReturn($metaData);
        $page = new UrlWizardPageTester($this->_config);

        $page->renderBodyContent();

        $this->expectOutputString(self::expectedBodyContent(array_merge($vars, $metaData, ['sites' => $sites, 'companies' => $companies])));
    }

    public function testRenderPageParamsNoSiteCompanyDir()
    {
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $siteId = 3;
        $sites = \Manx\Test\RowFactory::createResultRowsForColumns([ 'site_id', 'name', 'url', 'description', 'copy_base', 'low', 'live', 'display_order' ],
            [
                [ $siteId, 'bitsavers', 'http://bitsavers.org', '', 'http://bitsavers.org/pdf/', 'N', 'Y', 0 ],
                [ 58, 'VTDA', 'http://vtda.org', '', 'http://vtda.org/docs/', 'N', 'Y', 0 ]
            ]);
        $this->_db->expects($this->once())->method('getSites')->willReturn($sites);
        $companyId = 22;
        $companies = \Manx\Test\RowFactory::createResultRowsForColumns([ 'id', 'name' ],
            [
                [ $companyId, 'DEC' ],
                [ 23, 'IBM' ]
            ]);
        $pubs = \Manx\Test\RowFactory::createResultRowsForColumns([
                'pub_id', 'ph_part', 'ph_title', 'pub_has_online_copies',
                'ph_abstract', 'pub_has_toc', 'pub_superseded', 'ph_pub_date',
                'ph_revision', 'ph_company', 'ph_alt_part', 'ph_pub_type'
            ],
            [
                [ 2211, 'TK-001', "DIBOL User's Guide", 1, '', 0, 0, '1978-01', '', $companyId, '', 'D' ],
                [ 2212, 'TK-002', "DIBOL Programmer's Guide", 1, '', 0, 0, '1978-01', '', $companyId, '', 'D' ],
            ]);
        $this->_db->expects($this->once())->method('getCompanyList')->willReturn($companies);
        $this->_manx->expects($this->atLeastOnce())->method('getDatabase')->willReturn($this->_db);
        $siteUnknownId = 5522;
        $url = 'http://bitsavers.trailing-edge.com/pdf/dec/dibol/AA-BI77A-TK_DIBOL_for_Beginners_Apr1984.pdf';
        $vars = ['id' => $siteUnknownId, 'url' => $url];
        $this->_config['vars'] = $vars;
        $size = 10204;
        $part = 'AA-BI77A-TK';
        $title = 'DIBOL for Beginners';
        $pubDate = '1984-04';
        $metaData = [
            'url' => 'http://bitsavers.org/pdf/dec/dibol/AA-BI77A-TK_DIBOL_for_Beginners_Apr1984.pdf',
            'mirror_url' => $url,
            'size' => $size,
            'valid' => true,
            'site' => [
                'site_id' => $siteId,
                'name' => 'bitsavers',
                'url' => 'http://bitsavers.org',
                'description' => '',
                'copy_base' => 'http://bitsavers.org/pdf/',
                'low' => 'N',
                'live' => 'Y',
                'display_order' => 1
            ],
            'company' => $companyId,
            'part' => $part,
            'pub_date' => $pubDate,
            'title' => $title,
            'format' => 'PDF',
            'site_company_directory' => '',
            'site_company_parent_directory' => '',
            'pubs' => $pubs,
            'keywords' => $part . ' ' . $title
        ];
        $this->_urlMeta->expects($this->once())->method('determineData')->with($url)->willReturn($metaData);
        $page = new UrlWizardPageTester($this->_config);

        $page->renderBodyContent();

        $this->expectOutputString(self::expectedBodyContent(array_merge($vars, $metaData, ['sites' => $sites, 'companies' => $companies])));
    }

    private static function expectedSiteOptions($vars)
    {
        $options = [];
        foreach ($vars['sites'] as $site)
        {
            $selected = $site['site_id'] == $vars['site']['site_id'] ? ' selected="selected"' : '';
            $options[] = sprintf('<option value="%1$d"%2$s>%3$s</option>',
                $site['site_id'], $selected, $site['url']);
        }
        $options[] = '';
        return implode("\n", $options);
    }

    private static function expectHidden($id, $val)
    {
        return "<input type=\"hidden\" id=\"$id\" name=\"$id\" value=\"$val\" />\n";
    }

    private static function expectedSiteHidden($vars)
    {
        return self::expectHidden('copy_site', $vars['site']['site_id']);
    }

    private static function expectedCompanyHidden($vars)
    {
        return self::expectHidden('company_id', $vars['company']);
    }

    private static function expectedCompanyOptions($vars)
    {
        $options = [];
        foreach ($vars['companies'] as $company)
        {
            $selected = $company['id'] == $vars['company'] ? ' selected="selected"' : '';
            $options[] = sprintf('<option value="%1$d"%2$s>%3$s</option>',
                $company['id'], $selected, $company['name']);
        }
        $options[] = '';
        return implode("\n", $options);
    }

    private static function expectedPublicationOptions($vars)
    {
        $options = [];
        foreach ($vars['pubs'] as $pub)
        {
            $options[] = sprintf('<option value="%1$d">%2$s  %3$s</option>',
                $pub['pub_id'], $pub['ph_part'], $pub['ph_title']);
        }
        $options[] = '';
        return implode("\n", $options);
    }

    private static function expectedSiteUnknown($vars)
    {
        $id = $vars['id'];
        return <<<EOH

<fieldset id="site_unknown_field" class="hidden">
<input type="hidden" id="site_unknown_id" name="site_unknown_id" value="$id" />
</fieldset>

EOH;
    }

    private static function param($vars, $id)
    {
        return array_key_exists($id, $vars) ? $vars[$id] : '';
    }

    private static function expectedBodyContent($vars)
    {
        $idPresent = array_key_exists('id', $vars);
        $urlPresent = array_key_exists('url', $vars);
        $copyReadOnly = $urlPresent ? ' readonly="readonly"' : '';
        $copyUrl = self::param($vars, 'url');
        $copySize = array_key_exists('size', $vars) ? $vars['size'] : '0';
        $copyFormat = self::param($vars, 'format');
        $copyFormatClass = strlen($copyFormat) == 0 ? 'hidden' : '';
        $copySiteDisabled = $idPresent ? ' disabled="disabled"' : '';
        list($copySites, $copySiteHidden) = array_key_exists('sites', $vars) ?
            [self::expectedSiteOptions($vars), self::expectedSiteHidden($vars)] : ['', ''];
        $copySiteClass = strlen($copySites) == 0 ? 'hidden' : '';
        $siteUnknown = $idPresent ? self::expectedSiteUnknown($vars) : '';
        $pubDate = self::param($vars, 'pub_date');
        $part = self::param($vars, 'part');
        $mirrorUrl = self::param($vars, 'mirror_url');
        $mirrorClass = strlen($mirrorUrl) == 0 ? 'hidden' : '';
        $companies = array_key_exists('companies', $vars) ? self::expectedCompanyOptions($vars) : '';
        $companyClass = strlen($companies) == 0 ? 'hidden' : '';
        list($companyDisabled, $companyHidden) = array_key_exists('site_company_directory', $vars) && strlen($vars['site_company_directory']) > 0 ?
            [' disabled="disabled"', self::expectedCompanyHidden($vars)] : ['', ''];
        $pubClass = $urlPresent ? '' : 'hidden';
        $supersedeClass = $urlPresent ? '' : 'hidden';
        $keywords = self::param($vars, 'keywords');
        $publications = array_key_exists('pubs', $vars) ? self::expectedPublicationOptions($vars) : '';
        $title = self::param($vars, 'title');
        $copyLink = $urlPresent ? sprintf(' href="%s"', $vars['url']) : '';
        $copyLinkClass = $urlPresent ? '' : 'hidden';
        $copyTextClass = $urlPresent ? 'hidden' : '';

        return <<<EOH
<h1>URL Wizard</h1>

<div id="form_container">
<form id="wizard" action="url-wizard.php" method="POST" name="f">
$siteUnknown
<fieldset id="copy_fields">
<legend id="copy_legend"><a id="copy_link"$copyLink class="$copyLinkClass">Copy</a><span id="copy_text" class="$copyTextClass">Copy</span></legend>
<ul>

<li id="copy_url_field">
<label for="copy_url">Document URL</label>
<input type="text" id="copy_url" name="copy_url" size="60" maxlength="255"$copyReadOnly value="$copyUrl" />
<img id="copy_url_help_button" src="assets/help.png" width="16" height="16" />
<span id="copy_url_working" class="hidden working">Working...</span>
<div id="copy_url_help" class="hidden">The complete URL for the document.</div>
<div id="copy_url_error" class="error hidden"></div>
</li>

<li id="copy_mirror_url_field" class="$mirrorClass">
<label for="copy_mirror_url">Mirror Document URL</label>
<input type="text" id="copy_mirror_url" name="copy_mirror_url" size="60" maxlength="255" readonly="readonly" value="$mirrorUrl" />
<img id="copy_mirror_url_help_button" src="assets/help.png" width="16" height="16" />
<div id="copy_mirror_url_help" class="hidden">Read-only.  The URL of a mirrored document as originally entered.</div>
<div id="copy_mirror_url_error" class="error hidden"></div>
</li>

<li id="copy_format_field" class="$copyFormatClass">
<label for="copy_format">Format</label>
<input type="text" id="copy_format" name="copy_format" size="10" maxlength="10" value="$copyFormat" />
<img id="copy_format_help_button" src="assets/help.png" width="16" height="16" />
<div id="copy_format_help" class="hidden">The format of the document at the URL, i.e. PDF.</div>
<div id="copy_format_error" class="error hidden"></div>
</li>

<li id="copy_site_field" class="$copySiteClass">
<label for="copy_site">Site</label>
<select id="copy_site" name="copy_site"$copySiteDisabled>
<option value="-1">(New Site)</option>
$copySites</select>
$copySiteHidden</li>

<li id="copy_notes_field">
<label for="copy_notes">Notes</label>
<input type="text" id="copy_notes" name="copy_notes" size="60" maxlength="200" value="" />
<img id="copy_notes_help_button" src="assets/help.png" width="16" height="16" />
<div id="copy_notes_help" class="hidden">Notes about this copy of the publication.</div>
<div id="copy_notes_error" class="error hidden"></div>
</li>

<input type="hidden" id="copy_size" name="copy_size" value="$copySize" />

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
<input type="hidden" id="site_company_parent_directory" name="site_company_parent_directory" value="" />
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

<fieldset id="company_fields" class="$companyClass">
<legend id="company_legend">Company</legend>
<ul>

<li id="company_id_field">
<label for="company_id">Company</label>
<select id="company_id" name="company_id"$companyDisabled>
<option value="-1">(New Company)</option>
$companies</select>
$companyHidden</li>

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

<fieldset id="publication_fields" class="$pubClass">
<legend id="publication_legend">Publication</legend>
<ul>

<li id="pub_search_keywords_field">
<label for="pub_search_keywords">Search Keywords</label>
<input type="text" id="pub_search_keywords" name="pub_search_keywords" size="40" value="$keywords" />
<img id="pub_search_keywords_help_button" src="assets/help.png" width="16" height="16" />
<span id="pub_search_keywords_working" class="hidden working">Working...</span>
<div id="pub_search_keywords_help" class="hidden">Search keywords to locate a known publication.</div>
<div id="pub_search_keywords_error" class="error hidden"></div>
</li>

<li id="pub_pub_id_field">
<label for="pub_pub_id"><span id="pub_pub_id_label">Publication</span><a id="pub_pub_id_link" class="hidden">Publication</a></label>
<select id="pub_pub_id" name="pub_pub_id">
<option value="-1">(New Publication)</option>
$publications</select>
</li>

<li id="pub_history_ph_title_field">
<label for="pub_history_ph_title">Title</label>
<input type="text" id="pub_history_ph_title" name="pub_history_ph_title" size="60" maxlength="255" value="$title" />
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
<input type="text" id="pub_history_ph_pub_date" name="pub_history_ph_pub_date" size="10" maxlength="10" value="$pubDate" />
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
<input type="text" id="pub_history_ph_part" name="pub_history_ph_part" maxlength="50" value="$part" />
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

<fieldset id="supersession_fields" class="$supersedeClass">
<legend id="supersession_legend">Supersession</legend>
<ul>

<li id="supersession_search_keywords_field">
<label for="supersession_search_keywords">Search keywords</label>
<input type="text" id="supersession_search_keywords" name="supersession_search_keywords" size="40" value="$keywords" />
<img id="supersession_search_keywords_help_button" src="assets/help.png" width="16" height="16" />
<span id="supersession_search_keywords_working" class="hidden working">Working...</span>
<div id="supersession_search_keywords_help" class="hidden">Search keywords to locate publications superseded by or superseding this publication.</div>
<div id="supersession_search_keywords_error" class="error hidden"></div>
</li>

<li id="supersession_old_pub_field">
<label for="supersession_old_pub"><span id="supersession_old_pub_label">Supersedes</span><a id="supersession_old_pub_link" class="hidden">Supersedes</a></label>
<select id="supersession_old_pub" name="supersession_old_pub">
<option value="-1">(None)</option>
$publications</select>
</li>

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

    private static function copyData($url, $format, $site)
    {
        return [
            'copy_url' => urlencode($url),
            'copy_format' => $format,
            'copy_site' => $site,
            'copy_notes' => '',
            'copy_size' => '',
            'copy_md5' => '',
            'copy_credits' => '',
            'copy_amend_serial' => ''
        ];
    }

    private static function siteData($name = '', $url = '', $description = '', $copyBase = '')
    {
        return [
            'site_name' => $name,
            'site_url' => $url,
            'site_description' => $description,
            'site_copy_base' => $copyBase
        ];
    }

    private static function companyData($id, $name = '', $shortName = '', $sortName = '', $notes = '')
    {
        return [
            'company_id' => $id,
            'company_name' => $name,
            'company_short_name' => $shortName,
            'company_sort_name' => $sortName,
            'company_notes' => $notes
        ];
    }

    private static function pubHistoryData($title, $pubType, $pubDate = '', $revision = '', $abstract = '', $part = '', $keywords = '')
    {
        return [
            'pub_history_ph_title' => $title,
            'pub_history_ph_revision' => $revision,
            'pub_history_ph_pub_type' => $pubType,
            'pub_history_ph_pub_date' => $pubDate,
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
            'pub_history_ph_amend_serial' => ''
        ];
    }
}
