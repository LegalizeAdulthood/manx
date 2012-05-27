<?php

class Cookie
{
	public static function set($value)
	{
		// expires in 30 minutes
		setcookie('manxSession', $value);
	}

	public static function delete()
	{
		setcookie('manxSession', 'OUT', time() - 60);
	}
}

?>
