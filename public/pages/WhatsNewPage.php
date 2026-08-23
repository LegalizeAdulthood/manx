<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

// For SORT_ORDER_xxx
require_once __DIR__ . '/UnknownPathDefs.php';

use Pimple\Container;

class WhatsNewPage extends AdminPageBase
{
    /** @var IWhatsNewPageFactory */
    private $_factory;
    private $_timeStampProperty;
    private $_indexByDateUrl;
    private $_indexByDateFile;
    private $_baseUrl;
    private $_siteName;
    private $_menuType;
    private $_page;
    private $_title;
    private $_thisDir;
    private $_partRegexError;

    public function __construct(Container $config)
    {
        parent::__construct($config);
        $this->_siteName = $config['siteName'];
        $config['timeStampProperty'] = $this->_siteName . '_whats_new_timestamp';
        $this->_indexByDateUrl = $config['indexByDateUrl'];
        $this->_indexByDateFile = $config['indexByDateFile'];
        $this->_baseUrl = $config['baseUrl'];
        $this->_menuType = $config['menuType'];
        $this->_page = $config['page'];
        $this->_title = $config['title'];
        $this->_fileSystem = $config['fileSystem'];
        $this->_factory = $config['whatsNewPageFactory'];
        $vars = $config['vars'];
        $this->_parentDirId = array_key_exists('parentDir', $vars) ? $vars['parentDir'] : -1;
        $this->_thisDir = null;
        $this->_partRegexError = '';
    }

    protected function renderCacheControl()
    {
        $this->sendHeader("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        $this->sendHeader("Pragma: no-cache");
        $this->sendHeader("Expires: 0");
    }

    protected function getMenuType()
    {
        return $this->_menuType;
    }

    protected function getTitle()
    {
        $dir = $this->getThisDir()['path'];
        return strlen($dir) ? $this->_title . ' ' . $dir : $this->_title;
    }

    private function getThisDir()
    {
        if (is_null($this->_thisDir))
        {
            if ($this->_parentDirId != -1)
            {
                $this->_thisDir = $this->_manxDb->getSiteUnknownDir($this->_parentDirId);
            }
            else
            {
                $this->_thisDir = ['id' => -1, 'path' => '', 'parent_dir_id' => -1,
                    'part_regex' => '', 'ignored' => 0];
            }
        }
        return $this->_thisDir;
    }

    protected function postPage()
    {
        $this->savePartRegex();
        $this->ingestPreviewRows();
        $this->ignorePaths();
        PageBase::renderPage();
    }

    private static function startsWith($text, $needle)
    {
        return substr($text, 0, strlen($needle)) == $needle;
    }

    protected function ignorePaths()
    {
        $ignoredIds = [];
        foreach (array_keys($this->_vars) as $key)
        {
            if (self::startsWith($key, 'ignore'))
            {
                $ignoredIds[] = $this->_vars[$key];
            }
        }
        if (count($ignoredIds))
        {
            $this->_manxDb->ignoreSitePaths($ignoredIds);
        }
    }

    protected function savePartRegex()
    {
        if ($this->_parentDirId == -1
            || !array_key_exists('part_regex', $this->_vars))
        {
            return;
        }

        $partRegex = trim($this->_vars['part_regex']);
        if (!UrlMetaData::partRegexIsValid($partRegex))
        {
            $this->_partRegexError = 'Invalid part-number regex.';
            return;
        }

        $this->_manxDb->updateSiteUnknownDirPartRegex(
            $this->_parentDirId, $partRegex);
        $this->_thisDir = null;
    }

    protected function ingestPreviewRows()
    {
        if (!array_key_exists('ingest_preview', $this->_vars))
        {
            return;
        }

        $selectedIds = $this->selectedPreviewRowIds();
        if (count($selectedIds) == 0)
        {
            return;
        }

        $thisDir = $this->getThisDir();
        $files = $this->_manxDb->getSiteUnknownPaths(
            $this->_siteName, $this->_parentDirId);
        foreach ($this->previewRows($thisDir, $files) as $row)
        {
            if (array_key_exists($row['id'], $selectedIds))
            {
                $this->ingestPreviewRow($row);
            }
        }
    }

    private function selectedPreviewRowIds()
    {
        $selectedIds = [];
        foreach (array_keys($this->_vars) as $key)
        {
            if (preg_match('/^ingest[0-9]+$/', $key))
            {
                $selectedIds[$this->_vars[$key]] = true;
            }
        }
        return $selectedIds;
    }

    private function ingestPreviewRow($row)
    {
        if ($row['status'] != 'Accepted' || $row['pub_id'] == '')
        {
            return;
        }

        $copyId = $this->_manxDb->addCopy($row['pub_id'], $row['format'],
            $row['site_id'], $row['url'], '', 0, '', '', '');
        $this->_manxDb->updateIgnoredUnknownSingleDir($row['id']);
        $this->_manxDb->removeSiteUnknownPathById($row['id']);
    }

    private function renderPartRegexForm($thisDir)
    {
        if ($this->_parentDirId == -1)
        {
            return;
        }

        $siteName = htmlspecialchars($this->_siteName);
        $parentDirId = $this->_parentDirId;
        $partRegex = htmlspecialchars($thisDir['part_regex']);
        $error = '';
        if (strlen($this->_partRegexError) > 0)
        {
            $error = sprintf('<div class="error">%s</div>' . "\n",
                htmlspecialchars($this->_partRegexError));
        }

        print <<<EOH
<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="$siteName" />
<input type="hidden" name="parentDir" value="$parentDirId" />
<fieldset>
<legend>Directory Metadata</legend>
<label for="part_regex">Part Regex</label>
<input type="text" id="part_regex" name="part_regex" size="60" value="$partRegex" />
<input type="submit" value="Save" />
$error</fieldset>
</form>


EOH;
    }

    private function unknownFileInfos($thisDir, $files)
    {
        $infos = [];
        foreach ($files as $file)
        {
            $path = $file['path'];
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $format = $file['ignored'] == 1 ? ''
                : $this->_manxDb->getFormatForExtension($extension);
            $infos[] = [
                'id' => $file['id'],
                'site_id' => $file['site_id'],
                'path' => $path,
                'url' => $this->documentUrl($thisDir['path'], $path),
                'extension' => $extension,
                'format' => $format,
                'ignored' => $file['ignored'] == 1
                    || self::ignoreFormat($format)
            ];
        }
        return $infos;
    }

    protected function previewRows($thisDir, $files)
    {
        $companyId = $this->previewCompanyId($thisDir);
        if ($companyId <= 0)
        {
            return [];
        }
        return $this->previewRowsForFileInfos($thisDir,
            $this->unknownFileInfos($thisDir, $files), $companyId);
    }

    private function previewCompanyId($thisDir)
    {
        if ($this->_parentDirId == -1)
        {
            return -1;
        }
        return $this->_manxDb->getCompanyIdForSiteUnknownDir(
            $this->_siteName, $thisDir['path']);
    }

    private function previewRowsForFileInfos($thisDir, $fileInfos,
        $companyId = null)
    {
        if ($companyId === null)
        {
            $companyId = $this->previewCompanyId($thisDir);
        }
        if ($companyId <= 0)
        {
            return [];
        }

        $previewRows = [];
        foreach ($fileInfos as $fileInfo)
        {
            if ($fileInfo['ignored'])
            {
                continue;
            }
            $previewRows[] = $this->previewRow($thisDir, $companyId, $fileInfo);
        }
        return $previewRows;
    }

    private function previewRow($thisDir, $companyId, $fileInfo)
    {
        list($fileName, $fileBase, $extension) =
            UrlMetaData::extractFileNameExtension($fileInfo['path']);
        list($pubDate, $fileBase) = UrlMetaData::extractPubDate($fileBase);
        list($part, $fileBase, $regexResult) =
            $this->previewPart($fileBase, $thisDir['part_regex']);
        $title = UrlMetaData::titleForFileBase($fileBase);
        $existingCopy = $this->_manxDb->copyExistsForUrl($fileInfo['url']);
        $pubs = $part == '' || is_array($existingCopy) ? []
            : $this->_manxDb->getPublicationsForPartNumber($part, $companyId);
        $status = self::previewStatus(
            $part, $pubDate, $title, $pubs, $existingCopy, $regexResult);
        $pubId = $status == 'Accepted' ? $pubs[0]['pub_id'] : '';

        return [
            'id' => $fileInfo['id'],
            'site_id' => $fileInfo['site_id'],
            'pub_id' => $pubId,
            'path' => $fileInfo['path'],
            'url' => $fileInfo['url'],
            'part' => $part,
            'pub_date' => $pubDate,
            'title' => $title,
            'format' => $fileInfo['format'],
            'regex_result' => $regexResult,
            'matching_publication' =>
                self::matchingPublicationHtml($companyId, $part, $pubs),
            'existing_copy' => self::existingCopyHtml($existingCopy),
            'status' => $status
        ];
    }

    private function previewPart($fileBase, $partRegex)
    {
        if ($partRegex != '' && !UrlMetaData::partRegexIsValid($partRegex))
        {
            return ['', $fileBase, 'Invalid'];
        }
        if ($partRegex != '')
        {
            list($part, $fileBase) =
                UrlMetaData::extractPartNumberWithRegex($fileBase, $partRegex);
            return [$part, $fileBase, $part == '' ? 'No match' : 'Match'];
        }

        list($part, $fileBase) = UrlMetaData::extractPartNumber($fileBase);
        return [$part, $fileBase,
            $part == '' ? 'Default no match' : 'Default match'];
    }

    private static function previewStatus($part, $pubDate, $title, $pubs,
        $existingCopy, $regexResult)
    {
        if (is_array($existingCopy))
        {
            return 'Duplicate';
        }
        if ($regexResult == 'Invalid'
            || $regexResult == 'No match'
            || $regexResult == 'Default no match')
        {
            return 'Rejected';
        }
        if ($part == '' || $pubDate == '' || $title == '')
        {
            return 'Rejected';
        }
        if (count($pubs) != 1)
        {
            return 'Uncertain';
        }
        if ($pubs[0]['ph_part'] != $part)
        {
            return 'Uncertain';
        }
        return 'Accepted';
    }

    private static function detailsLink($companyId, $pubId, $title)
    {
        return sprintf('<a href="details.php/%s,%s">%s</a>',
            htmlspecialchars($companyId),
            htmlspecialchars($pubId),
            htmlspecialchars($title));
    }

    private static function matchingPublicationHtml($companyId, $part, $pubs)
    {
        if (count($pubs) == 0)
        {
            return 'None';
        }
        if (count($pubs) > 1)
        {
            return sprintf('<a href="search.php?cp=%s&amp;q=%s">%d candidates</a>',
                htmlspecialchars($companyId), htmlspecialchars(rawurlencode($part)),
                count($pubs));
        }
        return self::detailsLink(
            $companyId, $pubs[0]['pub_id'], $pubs[0]['ph_title']);
    }

    private static function existingCopyHtml($existingCopy)
    {
        if (!is_array($existingCopy))
        {
            return 'No';
        }
        return self::detailsLink($existingCopy['ph_company'],
            $existingCopy['ph_pub'], $existingCopy['ph_title']);
    }

    private function renderPreviewTable($previewRows)
    {
        if (count($previewRows) == 0)
        {
            return;
        }

        $siteName = htmlspecialchars($this->_siteName);
        $parentDirId = $this->_parentDirId;

        print <<<EOH
<h2>Ingestion Preview</h2>
<form id="ingest_preview_form" action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="$siteName" />
<input type="hidden" name="parentDir" value="$parentDirId" />
<input type="hidden" name="ingest_preview" value="1" />
<table>
<tr><th>Ingest?</th><th>File</th><th>Status</th><th>Part</th><th>Date</th><th>Title</th><th>Format</th><th>Regex</th><th>Matching Publication</th><th>Existing Copy</th></tr>

EOH;
        $i = 0;
        foreach ($previewRows as $row)
        {
            $checked = $row['status'] == 'Accepted' ? ' checked="checked"' : '';
            $disabled = $row['status'] == 'Accepted' ? '' : ' disabled="disabled"';
            printf('<tr><td><input type="checkbox" id="ingest%d" name="ingest%d" value="%d"%s%s/></td>',
                $i, $i, $row['id'], $checked, $disabled);
            printf('<td><a href="url-wizard.php?id=%d&url=%s">%s</a></td>',
                $row['id'], htmlspecialchars($row['url']),
                htmlspecialchars($row['path']));
            printf('<td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
                . '<td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>'
                . "\n",
                htmlspecialchars($row['status']),
                htmlspecialchars($row['part']),
                htmlspecialchars($row['pub_date']),
                htmlspecialchars($row['title']),
                htmlspecialchars($row['format']),
                htmlspecialchars($row['regex_result']),
                $row['matching_publication'],
                $row['existing_copy']);
            ++$i;
        }
        print <<<EOH
</table>
<script type="text/javascript">
function setIngestPreviewChecked(checked)
{
    var form = document.getElementById("ingest_preview_form");
    var boxes;
    var i;

    if (form === null)
    {
        return;
    }

    boxes = form.querySelectorAll('input[type="checkbox"][name^="ingest"]:not(:disabled)');
    for (i = 0; i < boxes.length; ++i)
    {
        boxes[i].checked = checked;
    }
}
</script>
<input type="button" id="ingest_check_all" value="Check All" onclick="setIngestPreviewChecked(true)" />
<input type="button" id="ingest_uncheck_all" value="Uncheck All" onclick="setIngestPreviewChecked(false)" />
<input type="submit" value="Ingest Selected" />
</form>

EOH;
    }

    protected function renderBodyContent()
    {
        $thisDir = $this->getThisDir();
        $currentDir = $thisDir['path'];
        $dirs = $this->_manxDb->getSiteUnknownDirectories(
            $this->_siteName, $this->_parentDirId);
        $files = $this->_manxDb->getSiteUnknownPaths($this->_siteName, $this->_parentDirId);
        $title = $this->_title;
        if (count($dirs) + count($files) == 0)
        {
            if ($this->_parentDirId == -1)
            {
                print <<<EOH
<h1>No New $title Publications Found</h1>

EOH;
            }
            else
            {
                print <<<EOH
<h1>No New $title $currentDir Publications Found</h1>


EOH;
                $this->renderPartRegexForm($thisDir);
                printf("<ul>\n<li><a href=\"%s&parentDir=%d#D%d\">(parent)</a></li>\n</ul>\n",
                    $this->_page, $thisDir['parent_dir_id'], $thisDir['id']);
            }
            return;
        }
        $fileInfos = $this->unknownFileInfos($thisDir, $files);

        print <<<EOH
<h1>New $title $currentDir Publications</h1>


EOH;

        $this->renderPartRegexForm($thisDir);
        $this->renderPreviewTable(
            $this->previewRowsForFileInfos($thisDir, $fileInfos));

        if ($this->_parentDirId != -1)
        {
            array_unshift($dirs,
                ['id' => $thisDir['parent_dir_id'],
                'path' => '(parent)',
                'parent_dir_id' => -1,
                'part_regex' => '']);
        }
        if (count($dirs) > 0)
        {
            printf("<ul>\n");
            foreach ($dirs as $dir)
            {
                if ($dir['path'] == '(parent)')
                {
                    printf('<li><a href="%s&parentDir=%d#D%d">%s</a></li>' . "\n",
                        $this->_page, $dir['id'], $thisDir['id'], $dir['path']);
                }
                else
                {
                    printf('<li><span id="D%d"><a href="%s&parentDir=%d">%s</a></span></li>' . "\n",
                        $dir['id'], $this->_page, $dir['id'], $dir['path']);
                }
            }
            printf("</ul>\n");
        }

        if (count($files) > 0)
        {
            $siteName = $this->_siteName;
            $parentDirId = $this->_parentDirId;
            $page = $this->_page;
            print <<<EOH
<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="$siteName" />
<input type="hidden" name="parentDir" value="$parentDirId" />
<table>
<tr><th>Ignored?</th><th>File</th></tr>

EOH;
            for ($i = 0; $i < count($fileInfos); ++$i)
            {
                $file = $fileInfos[$i];
                $path = $file['path'];
                $url = $file['url'];
                $checked = $file['ignored'] ? ' checked' : '';
                printf('<tr><td><input type="checkbox" id="ignore%1$d" name="ignore%1$d" value="%2$s"%5$s/></td>' . "\n"
                    .  '<td><a href="url-wizard.php?id=%2$d&url=%3$s">%4$s</a></td></tr>' . "\n",
                    $i, $file['id'], $url, htmlspecialchars($path), $checked);
            }
            print <<<EOH
</table>
<input type="submit" value="Ignore" />
</form>

EOH;
        }
    }

    public static function ignoreExtension(IManxDatabase $manxDb, $extension)
    {
        $format = $manxDb->getFormatForExtension($extension);
        return self::ignoreFormat($format);
    }

    private static function ignoreFormat($format)
    {
        $imageFormats = array('TIFF' => 1, 'PNG' => 1, 'JPEG' => 1, 'GIF' => 1);
        return strlen($format) == 0 || array_key_exists($format, $imageFormats);
    }

    private function documentUrl($dir, $path)
    {
        $relativePath = trim($path);
        if (strlen($dir) > 0)
        {
            $relativePath = trim($dir, '/') . '/' . $relativePath;
        }
        return UrlNormalizer::normalize($this->_baseUrl . '/' . $relativePath);
    }
}
