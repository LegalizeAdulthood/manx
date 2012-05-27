<?php
	require_once 'IManxDatabase.php';
	require_once 'IUser.php';
	require_once 'Cookie.php';

	class User implements IUser
	{
		private $_displayName;
		private $_loggedIn;

		public static function getInstance(IManxDatabase $manxDb)
		{
			return new User($manxDb);
		}

		private function __construct(IManxDatabase $manxDb)
		{
			$row = $manxDb->getUserFromSession();
			if (array_key_exists('first_name', $row))
			{
				if (time() - strtotime($row['last_impression']) > 30*60)
				{
					$manxDb->deleteUserSession();
					Cookie::delete();
				}
				else
				{
					$this->_displayName = $row['first_name'] . " " . $row['last_name'];
					$this->_loggedIn = $row['logged_in'] != 0;
					return;
				}
			}
			$this->_displayName = "Guest";
			$this->_loggedIn = false;
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
