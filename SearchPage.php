<?php

require_once 'PageBase.php';

class SearchPage extends PageBase
{
	protected function getMenuType()
	{
		return MenuType::Search;
	}

	protected function renderBodyContent()
	{
		$this->_manx->renderSearchResults();
	}
}

?>
