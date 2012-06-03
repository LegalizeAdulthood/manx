<?php

require_once 'IManxDatabase.php';
require_once 'IUser.php';
require_once 'Cookie.php';

class User implements IUser
{
	private $_userId;
	private $_loggedIn;
	private $_firstName;
	private $_lastName;
	private $_displayName;

	public static function getInstanceFromSession(IManxDatabase $manxDb)
	{
		return new User($manxDb);
	}

	private function __construct(IManxDatabase $manxDb)
	{
		$row = $manxDb->getUserFromSessionId(Cookie::get());
		if (array_key_exists('user_id', $row))
		{
			if (time() - strtotime($row['last_impression']) > 30*60)
			{
				$manxDb->deleteUserSession();
				Cookie::delete();
			}
			else
			{
				$this->_userId = $row['user_id'];
				$this->_loggedIn = $row['logged_in'] != 0;
				$this->_firstName = $row['first_name'];
				$this->_lastName = $row['last_name'];
				$this->_displayName = sprintf("%s %s", $this->_firstName, $this->_lastName);
			}
		}
		else
		{
			$this->_userId = -1;
			$this->_loggedIn = false;
			$this->_firstName = 'Guest';
			$this->_lastName = '';
			$this->_displayName = $this->_firstName;
		}
	}

	public function userId()
	{
		return $this->_userId;
	}

	public function isLoggedIn()
	{
		return $this->_loggedIn;
	}

	public function displayName()
	{
		return $this->_displayName;
	}
}

?>
