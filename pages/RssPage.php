<?php

require_once 'PageBase.php';
require_once 'RssWriter.php';
require_once 'IDateTimeProvider.php';

class RssPage extends PageBase
{
    private $_rss;

    public function __construct(IManx $manx, IDateTimeProvider $dateTimeProvider)
    {
        parent::__construct($manx);
        $this->_rss = new RssWriter($dateTimeProvider);
        $this->_rss->beginChannel('New Documents on Manx', 'http://manx.classiccmp.org',
                'A list of the most recently created documents in the Manx database.')
            ->language('en-us');
        foreach ($this->_manxDb->getMostRecentDocuments(200) as $pub)
        {
            $pubId = $pub['ph_pub'];
            $link = sprintf('details.php/%d,%d', $pub['ph_company'], $pubId);
            $description = $this->getItemDescription($pub);
            $pubDate = new DateTime($pub['ph_created']);
            $this->_rss->item($pub['ph_title'], $link, $description,
                array(
                    'pubDate' => $pubDate->format(DateTime::RFC1123),
                    'category' => $pub['company_short_name']
                ));
        }
    }

    public function getItemDescription($row)
    {
        $companyId = $row['ph_company'];
        ob_start();
        echo '<div style="margin: 10px"><p><strong style="color: #089698; background-color: transparent;">', $row['ph_title'], "</strong></p>\n";
        echo '<table><tbody>';
        $this->printTableRowNoEncode('Company',
            sprintf('<a href="../search.php?cp=%d&q=">%s</a>',
                $companyId,
                htmlspecialchars(trim($row['company_name']))
            ));
        $this->printTableRow('Part', $row['ph_part'] . ' ' . $row['ph_revision']);
        $this->printTableRowFromDatabaseRow($row, 'Date', 'ph_pubdate');
        $this->printTableRowFromDatabaseRow($row, 'Keywords', 'ph_keywords');
        $pubId = $row['ph_pub'];
        $abstract = RssPage::replaceNullWithEmptyStringOrTrim($row['ph_abstract']);
        if (strlen($abstract) > 0)
        {
            $this->printTableRow('Text', $abstract);
        }
        print "</tbody>\n</table>\n</div>\n";
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    public static function replaceNullWithEmptyStringOrTrim($value)
    {
        return is_null($value) ? '' : trim($value);
    }

    private function printTableRowNoEncode($name, $value)
    {
        if (strlen($value) > 0)
        {
            echo '<tr><td>', $name, ':</td><td>', $value, "</td></tr>\n";
        }
    }

    private function printTableRow($name, $value)
    {
        $this->printTableRowNoEncode($name, htmlspecialchars(trim($value)));
    }

    private function printTableRowFromDatabaseRow($row, $name, $key)
    {
        $this->printTableRow($name, $row[$key]);
    }

    protected function renderHeader()
    {
        $this->_rss->renderHeader();
    }

    protected function renderBody()
    {
        $this->_rss->renderBody();
    }

    protected function renderBodyContent()
    {
        throw new Exception("renderBodyContent not used");
    }
}
