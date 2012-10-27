<?php

interface IDatabase
{
	function query($statement);
	function execute($statement, $args);
	function getLastInsertId();
}

?>
