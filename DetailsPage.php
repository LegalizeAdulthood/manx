<?php

require_once 'PageBase.php';

class DetailsPage extends PageBase
{
	private $_details;

	public function __construct(IManx $manx)
	{
		parent::__construct($manx);
		$this->_details = $this->_manx->getDetailsForPathInfo($_SERVER['PATH_INFO']);
	}

	protected function getTitle()
	{
		return $this->_details[1]['ph_title'];
	}

	protected function renderBodyContent()
	{
		$this->_manx->renderDetails($this->_details);
	}
}

?>
