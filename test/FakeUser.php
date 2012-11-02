<?php

require_once 'pages/IUser.php';

class FakeUser implements IUser
{
	public function isLoggedIn()
	{
		return true;
	}
	function displayName()
	{
		throw new Exception("displayName not implemented");
	}
	function isAdmin()
	{
		return true;
	}
	function userId()
	{
		throw new Exception("userId not implemented");
	}
}
