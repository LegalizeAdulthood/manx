<?php

class FakeStatement
{
	public function __construct()
	{
		$this->fetchCalled = false;
		$this->fetchAllCalled = false;
	}

	public $fetchCalled;
	public $fetchFakeResult;
	public function fetch()
	{
		$this->fetchCalled = true;
		return $this->fetchFakeResult;
	}

	public $fetchAllCalled;
	public $fetchAllFakeResult;
	public function fetchAll()
	{
		$this->fetchAllCalled = true;
		return $this->fetchAllFakeResult;
	}
}

?>
