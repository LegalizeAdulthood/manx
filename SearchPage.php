<?php

require_once 'PageBase.php';
require_once 'HtmlFormatter.php';
require_once 'Searcher.php';

class SearchPage extends PageBase
{
	protected function getMenuType()
	{
		return MenuType::Search;
	}

	protected function renderBodyContent()
	{
		$params = Searcher::parameterSource($_GET, $_POST);
		$searcher = Searcher::getInstance($this->_manxDb);
		print '<div id="Div1"><form action="search.php" method="get" name="f"><div class="field">Company: ';
		$company = (array_key_exists('cp', $params) ? $params['cp'] : 1);
		$keywords = urldecode(array_key_exists('q', $params) ? $params['q'] : '');
		$searcher->renderCompanies($company);
		print ' Keywords: <input id="q" name="q" size="20" maxlength="256" '
			. (array_key_exists('q', $params) ? ' value="' . $keywords . '"' : '')
			. '/> '
			. 'Online only: <input type="checkbox" name="on" '
			. (array_key_exists('on', $params) ? 'checked="checked" ' : '')
			. '/> '
			. '<input id="Submit1" type="submit" value="Search" /></div></form></div>';
		$formatter = HtmlFormatter::getInstance();
		$online = array_key_exists('on', $params) && ($params['on'] != '0');
		$searcher->renderSearchResults($formatter, $company, $keywords, $online);
	}
}

?>
