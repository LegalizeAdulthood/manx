<?php

require_once 'PageBase.php';

use Pimple\Container;

class DetailsPage extends PageBase
{
    private $_details;

    public function __construct(Container $config)
    {
        parent::__construct($config);
        $this->_details = $this->getDetailsForPathInfo($_SERVER['PATH_INFO']);
    }

    protected function getTitle()
    {
        return $this->_details[1]['ph_title'];
    }

    private static function formatPubDate($pubDate)
    {
        $pubDate = DetailsPage::replaceNullWithEmptyStringOrTrim($pubDate);
        if (strlen($pubDate) > 0)
        {
            return ' (' . htmlspecialchars($pubDate) . ')';
        }
        return '';
    }

    public static function replaceNullWithEmptyStringOrTrim($value)
    {
        return is_null($value) ? '' : trim($value);
    }

    public static function formatDocRef($row)
    {
        $out = sprintf('<a href="../details.php/%d,%d"><cite>%s</cite></a>', $row['ph_company'], $row['ph_pub'], htmlspecialchars($row['ph_title']));
        return DetailsPage::partPrefix($row['ph_part']) . $out;
    }

    private static function partPrefix($part)
    {
        $part = DetailsPage::replaceNullWithEmptyStringOrTrim($part);
        if (strlen($part) > 0)
        {
            return htmlspecialchars($part) . ', ';
        }
        return '';
    }

    protected function renderBodyContent()
    {
        $params = $this->_details[0];
        $row = $this->_details[1];
        $coverImage = $row['ph_cover_image'];
        if (!is_null($coverImage))
        {
            printf('<div style="float:right; margin: 10px"><img src="%s" alt="" /></div>', $coverImage);
        }
        echo '<div class="det"><h1>', $row['ph_title'], "</h1>\n";
        echo '<table><tbody>';
        $this->printTableRowNoEncode('Company',
            sprintf('<a href="../search.php?cp=%d&q=">%s</a>',
                $params['cp'],
                htmlspecialchars(trim($row['name']))
            ));
        $this->printTableRow('Part', $row['ph_part'] . ' ' . $row['ph_revision']);
        $this->printTableRowFromDatabaseRow($row, 'Date', 'ph_pub_date');
        $this->printTableRowFromDatabaseRow($row, 'Keywords', 'ph_keywords');
        $this->renderLanguage($row['ph_lang']);
        $pubId = $row['pub_id'];
        $this->renderAmendments($pubId);
        $this->renderOSTags($pubId);
        $this->renderLongDescription($pubId);
        $this->renderCitations($pubId);
        $this->renderSupersessions($pubId);
        $abstract = DetailsPage::replaceNullWithEmptyStringOrTrim($row['ph_abstract']);
        if (strlen($abstract) > 0)
        {
            $this->printTableRow('Text', $abstract);
        }
        echo "</tbody>\n</table>\n";
        $fullContents = array_key_exists('cn', $params) && ($params['cn'] == 1);
        $this->renderTableOfContents($pubId, $fullContents);
        $this->renderCopies($pubId);
        print "</div>\n";
    }

    private function printTableRowNoEncode($name, $value)
    {
        echo '<tr><td>', $name, ':</td><td>', $value, "</td></tr>\n";
    }

    private function printTableRow($name, $value)
    {
        $this->printTableRowNoEncode($name, htmlspecialchars(trim($value)));
    }

    private function printTableRowFromDatabaseRow($row, $name, $key)
    {
        $this->printTableRow($name, $row[$key]);
    }

    public function renderLanguage($lang)
    {
        if (!is_null($lang) && $lang != '+en')
        {
            $languages = array();
            foreach (array_slice(explode('+', $lang), 1) as $languageCode)
            {
                array_push($languages, $this->_manxDb->getDisplayLanguage($languageCode));
            }
            if (count($languages) > 0)
            {
                echo '<tr><td>Language', (count($languages) > 1) ? 's' : '', ':</td><td>', DetailsPage::neatListPlain($languages), "</td></tr>\n";
            }
        }
    }

    public function renderAmendments($pubId)
    {
        $amendments = array();
        foreach ($this->_manxDb->getAmendmentsForPub($pubId) as $row)
        {
            $amend = sprintf('<a href="../details.php/%d,%d"><cite>%s</cite></a>', $row['ph_company'], $row['ph_pub'], htmlspecialchars($row['ph_title']));
            $amend = DetailsPage::partPrefix($row['ph_part']) . $amend;
            $amend .= DetailsPage::formatPubDate($row['ph_pub_date']);
            $amend .= $this->renderOSTagsForPub($pubId);
            array_push($amendments, $amend);
        }
        if (count($amendments) > 0)
        {
            echo '<tr valign="top"><td>Amended&nbsp;by:</td><td><ul class="citelist"><li>', implode('</li><li>', $amendments), "</li></ul></td></tr>\n";
        }
    }

    private function renderOSTagsForPub($pubId)
    {
        $tags = $this->_manxDb->getOSTagsForPub($pubId);
        if (count($tags) > 0)
        {
            return ' <b>OS:</b> ' . htmlspecialchars(implode(', ', $tags));
        }
        return '';
    }

    public function renderOSTags($pubId)
    {
        $tags = $this->_manxDb->getOSTagsForPub($pubId);
        if (count($tags) > 0)
        {
            echo '<tr><td>Operating System:</td><td>', htmlspecialchars(implode(', ', $tags)), "</td></tr>\n";
        }
    }

    public function renderLongDescription($pubId)
    {
        $startedDesc = false;
        foreach ($this->_manxDb->getLongDescriptionForPub($pubId) as $html)
        {
            if (!$startedDesc)
            {
                echo '<tr valign="top"><td>Description:</td><td>';
                $startedDesc = true;
            }
            print $html;
        }
        if ($startedDesc)
        {
            echo '</td></tr>';
        }
    }

    public function renderCitations($pubId)
    {
        // Citations from other documents (only really important when there are no copies online)
        $citations = array();
        foreach ($this->_manxDb->getCitationsForPub($pubId) as $row)
        {
            array_push($citations, DetailsPage::formatDocRef($row));
        }
        if (count($citations) > 0)
        {
            echo '<tr valign="top"><td>Cited by:</td><td><ul class="citelist"><li>', implode('</li><li>', $citations), "</li></ul></td></tr>\n";
        }
    }

    public function renderSupersessions($pubId)
    {
        // Supersession information. Because documents can be merged in later revisions,
        // or expand to become more than one, there may be more than one document that
        // preceded or superseded this one.
        $supers = array();
        foreach ($this->_manxDb->getPublicationsSupersededByPub($pubId) as $pub)
        {
            array_push($supers, DetailsPage::formatDocRef($pub));
        }
        if (count($supers) > 0)
        {
            echo '<tr valign="top"><td>Supersedes:</td><td><ul class="citelist"><li>',
                implode('</li><li>', $supers), "</li></ul></td></tr>\n";
        }
        $supers = array();
        foreach ($this->_manxDb->getPublicationsSupersedingPub($pubId) as $pub)
        {
            array_push($supers, DetailsPage::formatDocRef($pub));
        }
        if (count($supers) > 0)
        {
            echo '<tr valign="top"><td>Superseded by:</td><td><ul class="citelist"><li>',
                implode('</li><li>', $supers), "</li></ul></td></tr>\n";
        }
    }

    public function renderTableOfContents($pubId, $fullContents)
    {
        $currentLevel = 0;
        $startedContents = false;
        foreach ($this->_manxDb->getTableOfContentsForPub($pubId, $fullContents) as $row)
        {
            if (!$startedContents)
            {
                print "<h2>Table of Contents</h2>\n";
                print '<div class="toc">';
                $startedContents = true;
            }
            $rowLevel = $row['level'];
            $rowLabel = $row['label'];
            $rowName = $row['name'];
            if ($rowLevel > $currentLevel)
            {
                ++$currentLevel;
                print "\n<ul>\n";
            }
            else if ($rowLevel < $currentLevel)
            {
                print "</li>\n";
                while ($rowLevel < $currentLevel)
                {
                    print "</ul></li>\n";
                    --$currentLevel;
                }
            }
            else
            {
                print "</li>\n";
            }
            if (is_null($rowLabel) && $currentLevel > 1)
            {
                $rowLabel = '&nbsp;';
            }
            printf('<li class="level%d"><span%s>%s</span> %s',
                $currentLevel, ($currentLevel == 1 ? ' class="level1"' : ''), $rowLabel, htmlspecialchars($rowName));
        }
        if ($startedContents)
        {
            while (0 < $currentLevel--)
            {
                print "</li>\n</ul>";
            }
            print '</div>';
        }
    }

    public function renderCopies($pubId)
    {
        print "<h2>Copies</h2>\n";
        $copyCount = 0;
        foreach ($this->_manxDb->getCopiesForPub($pubId) as $row)
        {
            if (++$copyCount == 1)
            {
                print "<table>\n<tbody>";
            }
            else
            {
                print "<tr>\n<td colspan=\"2\">&nbsp;</td>\n</tr>\n";
            }

            print "<tr>\n<td>Address:</td>\n<td>";
            $copyUrl = $row['url'];
            if (substr($copyUrl, 0, 1) == '+')
            {
                $copyUrl = $row['copy_base'] . substr($copyUrl, 1);
            }
            printf("<a href=\"%s\">%s</a></td>\n</tr>\n", $copyUrl, $copyUrl);
            printf("<tr>\n<td>Site:</td>\n<td><a href=\"%s\">%s</a>", htmlspecialchars($row['site_url']), htmlspecialchars($row['description']));
            if ($row['low'] != 'N')
            {
                print ' <span class="warning">(Low Bandwidth)</span>';
            }
            print "</td>\n</tr>\n";
            printf("<tr>\n<td>Format:</td>\n<td>%s</td>\n</tr>\n", htmlspecialchars($row['format']));
            $size = $row['size'];
            if ($size > 0)
            {
                printf("<tr>\n<td>Size:</td>\n<td>%d bytes", $size);
                $sizeMegabytes = $size/(1024*1024);
                $sizeKilobytes = $size/1024;
                if ($sizeMegabytes > 1.0)
                {
                    printf(" (%.1f MiB)", $sizeMegabytes);
                }
                else if ($sizeKilobytes > 1.0)
                {
                    printf(" (%.0f KiB)", $sizeKilobytes);
                }
                print "</td>\n</tr>\n";
            }
            $md5 = DetailsPage::replaceNullWithEmptyStringOrTrim($row['md5']);
            if (strlen($md5) > 0)
            {
                printf("<tr>\n<td>MD5:</td>\n<td>%s</td>\n</tr>\n", htmlspecialchars($md5));
            }
            $notes = DetailsPage::replaceNullWithEmptyStringOrTrim($row['notes']);
            if (strlen($notes) > 0)
            {
                printf("<tr>\n<td>Notes:</td>\n<td>%s</td>\n</tr>\n", htmlspecialchars($notes));
            }
            $credits = DetailsPage::replaceNullWithEmptyStringOrTrim($row['credits']);
            if (strlen($credits) > 0)
            {
                printf("<tr>\n<td>Credits:</td><td>%s</td>\n</tr>\n", htmlspecialchars($credits));
            }
            $amendSerial = $row['amend_serial'];
            if (!is_null($amendSerial))
            {
                $amendRow = $this->_manxDb->getAmendedPub($pubId, $amendSerial);
                $amend = sprintf("<a href=\"../details.php/%d,%d\"><cite>%s</cite></a>",
                    $amendRow['ph_company'], $amendRow['pub_id'], htmlspecialchars($amendRow['ph_title']));
                $amend = DetailsPage::partPrefix($amendRow['ph_part']) . $amend;
                $amend .= DetailsPage::formatPubDate($amendRow['ph_pub_date']);
                $amend .= $this->renderOSTagsForPub($amendRow['pub_id']);
                printf("<tr>\n<td>Amended to:</td>\n<td>%s</td>\n</tr>\n", $amend);
            }

            $mirrorCount = 0;
            foreach ($this->_manxDb->getMirrorsForCopy($row['copy_id']) as $mirror)
            {
                if (++$mirrorCount == 1)
                {
                    print '<tr valign="top"><td>Mirrors:</td><td><ul style="list-style-type: none; margin: 0; padding: 0">';
                }
                printf("<li style=\"margin: 0; padding: 0\"><a href=\"%s\">%s</a></li>", $mirror, htmlspecialchars($mirror));
            }
            if ($mirrorCount > 0)
            {
                print '</ul></td></tr>';
            }
        }
        if ($copyCount > 0)
        {
            print "</tbody>\n</table>\n";
        }
        else
        {
            print <<<EOH
<p>No copies are known to be online.  Feel
free to <a href="http://manx.codeplex.com/WorkItem/Create">create a bug
report</a> on our <a href="http://manx.codeplex.com">CodePlex project</a> if
you know of an online copy of this publication.</p>

EOH;
        }
    }

    public static function detailParamsForPathInfo($pathInfo)
    {
        $matches = array();
        $params = array();
        if (1 == preg_match_all('/^\\/(\\d+),(\\d+)$/', $pathInfo, $matches))
        {
            $params['cp'] = $matches[1][0];
            $params['id'] = $matches[2][0];
            $params['cn'] = 1;
            $params['pn'] = 0;
        }
        return $params;
    }

    private function getDetailsForPathInfo($pathInfo)
    {
        $params = DetailsPage::detailParamsForPathInfo($pathInfo);
        return array($params, $this->_manxDb->getDetailsForPub($params['id']));
    }

    public static function neatListPlain($values)
    {
        if (count($values) > 1)
        {
            return implode(', ', array_slice($values, 0, count($values) - 1)) . ' and ' . $values[count($values) - 1];
        }
        else
        {
            return $values[0];
        }
    }
}
