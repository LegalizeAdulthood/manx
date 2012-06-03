<?php

require_once 'PageBase.php';

class PublicationPage extends PageBase
{
	private $_vars;

	public function __construct($manx, $vars)
	{
		parent::__construct($manx);
		$this->_vars = vars;
	}

	protected function getMenuType()
	{
		return MenuType::Publication;
	}

	protected function renderHeaderContent()
	{
		print <<<EOH
<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="PartLookup.js"></script>
EOH;
	}

	private function param($name, $defaultValue = '')
	{
		if (array_key_exists($name, $this->_vars))
		{
			return $this->_vars[$name];
		}
		else
		{
			return $defaultValue;
		}
	}

	private function quotedParam($name)
	{
		return htmlspecialchars($this->param($name));
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
		foreach ($this->_manx->getCompanyList() as $row)
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
<input type="text" id="keywords" name="keywords" value="$keywrods"></li>
<li><label for="notes">Notes:</label>
<input type="text" id="notes" name="notes" value="$notes"></li>
<li><label for="lang">Language(s):</label>
<input type="text" id="lang" name="lang" value="$language"></li>
</ul></fieldset>

<input type="submit" name="opsave" value="Save">
</form></div>

EOH;
	}

	private function redirect($target)
	{
		header("Status: 303 See Also");
		header("Location: " . $target);
		header("Content-Type: text/plain");
		print "Redirecting to " . $target;
	}

	public function renderPage()
	{
		if (!$this->_user->isLoggedIn())
		{
			$this->redirect("login.php?redirect=" . urlencode($_SERVER['PHP_SELF']));
			return;
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST')
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
			$languages = $this->param('lang', '+en');

			$pubId = $this->_manx->addPublication($this->_user,
				$company, $part, $pubDate, $title,
				$publicationType, $altPart, $revision, $keywords, $notes,
				$languages);

			$this->redirect(sprintf("details.php/%s,%s", $company, $pubId));
			return;
		}

		parent::renderPage();
	}
}

?>
