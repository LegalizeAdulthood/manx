<?php

require_once 'PageBase.php';

abstract class AdminPageBase extends PageBase
{
	protected $_vars;

	public function __construct($manx, $vars)
	{
		parent::__construct($manx);
		$this->_vars = $vars;
	}

	protected function param($name, $defaultValue = '')
	{
		if (array_key_exists($name, $this->_vars))
		{
			return rawurldecode($this->_vars[$name]);
		}
		else
		{
			return $defaultValue;
		}
	}

	protected function quotedParam($name)
	{
		return htmlspecialchars($this->param($name));
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
			$this->postPage();
			return;
		}

		parent::renderPage();
	}

	protected abstract function postPage();
}
