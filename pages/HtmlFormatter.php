<?php

require_once 'IFormatter.php';

class HtmlFormatter implements IFormatter
{
    public static function getInstance()
    {
        return new HtmlFormatter();
    }
    private function __construct()
    {
    }

    public static function neatQuotedList(array $words)
    {
        if (count($words) > 1)
        {
            return '"' . implode('", "', array_slice($words, 0, count($words) - 1))
                . '" and "' . $words[count($words) - 1] . '"';
        }
        else
        {
            return '"' . $words[0] . '"';
        }
    }

    public function renderResultsBar(array $ignoredWords, array $searchWords, int $start, int $end, int $total)
    {
        if (count($ignoredWords) > 0)
        {
            print '<p class="warning">Ignoring ' . HtmlFormatter::neatQuotedList($ignoredWords)
                . '.  All search words must be at least three letters long.</p>';
        }
        print '<div class="resbar">';
        if (count($searchWords) > 0)
        {
            print 'Searching for ' . HtmlFormatter::neatQuotedList($searchWords) . '.';
        }
        else
        {
            print 'Showing all documents.';
        }
        print ' Results <b>' . ($start + 1) . ' - ' . ($end + 1) . '</b> of <b>' . $total . '</b>.</div>';
    }

    public function renderPageSelectionBar(int $start, int $total, int $rowsPerPage, array $params)
    {
        $encodedQuery = urlencode(array_key_exists('q', $params) ? $params['q'] : '');
        print '<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;';
        $linkOptions = ($rowsPerPage != DEFAULT_ROWS_PER_PAGE ? '&num=' . $rowsPerPage : '')
            . (array_key_exists('on', $params) && ($params['on'] == 'on') ? '&on=on' : '')
            . (array_key_exists('cp', $params) ? '&cp=' . $params['cp'] : '');
        if ($start != 0)
        {
            printf('<a href="search.php?q=%s&start=%s%s"><b>Previous</b></a>&nbsp;&nbsp;',
                $encodedQuery, max(0, $start - $rowsPerPage), $linkOptions);
        }

        $firstPage = intval($start /(10*$rowsPerPage))*10 + 1;
        $lastPageNum = intval(($total + $rowsPerPage - 1)/$rowsPerPage);
        $lastPageStart = ($lastPageNum - 1)*$rowsPerPage;
        $currPageNum = $firstPage;
        $currPageStart = ($currPageNum - 1)*$rowsPerPage;

        $numIndices = 0;
        while ($numIndices++ < 10)
        {
            if ($start == $currPageStart)
            {
                print '<b class="currpage">' . $currPageNum . '</b>&nbsp;&nbsp;';
            }
            else
            {
                print '<a class="navpage" href="search.php?q='
                    . $encodedQuery . '&start=' . $currPageStart . $linkOptions
                    . '">' . $currPageNum . '</a>&nbsp;&nbsp;';
            }
            ++$currPageNum;
            $currPageStart += $rowsPerPage;
            if ($currPageStart > $lastPageStart)
            {
                break;
            }
        }
        if ($start != $lastPageStart)
        {
            print '<a href="search.php?q=' . $encodedQuery
                . '&start=' . ($start + $rowsPerPage) . $linkOptions . '"><b>Next</b></a>';
        }
        print '</div>';
    }

    private function replaceNullWithEmptyString(array $row)
    {
        foreach (array_keys($row) as $key)
        {
            if (is_null($row[$key]))
            {
                $row[$key] = '';
            }
        }
        return $row;
    }

    public function renderResultsPage(array $rows, int $start, int $end)
    {
        print '<table class="restable"><thead><tr><th>Part</th><th>Date</th><th>Title</th><th class="last">Status</th></tr></thead><tbody>';
        for ($i = $start; $i <= $end; $i++)
        {
            $row = $this->replaceNullWithEmptyString($rows[$i]);
            print '<tr valign="top">';
            print '<td>' . htmlspecialchars(trim($row['ph_part'] . ' ' . $row['ph_revision']));
            if ($row['ph_alt_part'] != '')
            {
                print '<br/><small>' . htmlspecialchars($row['ph_alt_part']) . '</small>';
            }
            print '</td><td>' . htmlspecialchars($row['ph_pub_date']) . '</td>';
            print '<td><a';
            if ($row['pub_superseded'] || $row['ph_pub_type'] == 'A')
            {
                print ' class="ss"';
            }
            printf(' href="details.php/%s,%s">%s</a>', $row['ph_company'], $row['pub_id'], htmlspecialchars($row['ph_title']));
            if (count($row['tags']) > 0)
            {
                echo '<br /><small><b>OS:</b> ',
                    htmlspecialchars(implode(', ', $row['tags'])), '</small>';
            }
            print '</td>';
            print '<td>';
            $flags = array();
            if ($row['pub_has_online_copies'])
            {
                array_push($flags, 'Online');
            }
            if ($row['pub_superseded'])
            {
                array_push($flags, 'Superseded');
            }
            if ($row['ph_pub_type'] == 'A')
            {
                array_push($flags, 'Amendment');
            }
            if ($row['pub_has_toc'])
            {
                array_push($flags, 'ToC');
            }
            if (count($flags) > 0)
            {
                print implode(', ', $flags);
            }
            else
            {
                print '&nbsp;';
            }
            print '</td></tr>';
        }
        print '</tbody></table>';
    }
}
