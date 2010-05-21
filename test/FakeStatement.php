<?php
	class FakeStatement
	{
		public $fetchCalled;
		public $fetchFakeResult;
		
		public function __construct($fetchResult)
		{
			$this->fetchCalled = false;
			$this->fetchFakeResult = $fetchResult;
		}
		
		public function fetch()
		{
			$this->fetchCalled = true;
			return $this->fetchFakeResult;			
		}
	}	
?>
