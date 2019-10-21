<?php

use Pimple\Container;

require_once 'AdminPageBase.php';
require_once 'IManx.php';

class PublicationPage extends AdminPageBase
{
    protected function getMenuType()
    {
        return MenuType::Publication;
    }

    protected function renderHeaderContent()
    {
        print <<<EOH
<script type="text/javascript" src="assets/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="assets/PartLookup.js"></script>
EOH;
    }

    protected function renderBodyContent()
    {
        print <<<EOH
<h1>Add Publication</h1>

<div id="addformdiv"><form id="addform" action="publication.php" method="POST" name="f">
<fieldset><legend id="plum">Essentials</legend><ul>
<li><label for="company">Company:</label><select id="company" name="company">
EOH;
        $selected = -1;
        foreach ($this->_manxDb->getCompanyList() as $row)
        {
            $id = $row['id'];
            printf('<option value="%s"%s>%s</option>' . "\n",
                $id, ($id == $selected ? ' selected' : ''),
                htmlspecialchars($row['name']));
        }
        $part = $this->quotedParam('part');
        $pubDate = $this->quotedParam('pubdate');
        $title = $this->quotedParam('title');
        $documentSelected = $this->param('pt') == 'D' ? ' selected' : '';
        $addendumSelected = $this->param('pt') == 'A' ? ' selected' : '';
        $altPart = $this->quotedParam('altpart');
        $revision = $this->quotedParam('revision');
        $keywords = $this->quotedParam('keywords');
        $notes = $this->quotedParam('notes');
        $language = $this->quotedParam('lang');
        print <<<EOH
</select>

<li><label for="part">Part or order no.:</label>
<input type="text" id="part" name="part" value="$part">
<button id="lkpt">Lookup</button>
<div id="partlist"></div>
</li>
<li><label for="pubdate">Publication date:</label>
<input type="text" id="pubdate" name="pubdate" value="$pubDate" size="10" maxlength="10"></li>
<li><label for="title">Title:</label>
<input type="text" id="title" name="title" value="$title" size="40"></li>
</ul></fieldset>

<fieldset><legend>Other bits</legend><ul>
<li><label for="pt">Publication type:</label>
<select id="pt" name="pt">
<option value="D"$documentSelected>document</option>
<option value="A"$addendumSelected>addendum</option>'
</select></li>

<li><label for="altpart">Alternative part no.:</label>
<input type="text" id="altpart" name="altpart" value="$altPart"></li>
<li><label for="revision">Revision:</label>
<input type="text" id="revision" name="revision" value="$revision"></li>
<li><label for="keywords">Keywords:</label>
<input type="text" id="keywords" name="keywords" value="$keywords"></li>
<li><label for="notes">Notes:</label>
<input type="text" id="notes" name="notes" value="$notes"></li>
<li><label for="lang">Language(s):</label>
<input type="text" id="lang" name="lang" value="$language"></li>
</ul></fieldset>

<input type="submit" name="opsave" value="Save">
</form></div>

EOH;
    }

    public function postPage()
    {
        $company = $this->param('company');
        $part = $this->param('part');
        $pubDate = $this->param('pubdate');
        $title = $this->param('title');

        $publicationType = $this->param('pt');
        $altPart = $this->param('altpart');
        $revision = $this->param('revision');
        $keywords = $this->param('keywords');
        $notes = $this->param('notes');
        $abstract = $this->param('abstract');
        $languages = $this->param('lang', '+en');

        $pubId = $this->_manx->addPublication($this->_user,
            $company, $part, $pubDate, $title, $publicationType,
            $altPart, $revision, $keywords, $notes, $abstract,
            $languages);

        $this->redirect(sprintf("details.php/%s,%s", $company, $pubId));
    }
}
