<?php

require_once 'pages/ICurlApi.php';

class FakeCurlApi implements ICurlApi
{
	public $initCalled = false;
	public $initLastUrl;
	public $initFakeResult;
	public $setoptCalled = false;
	public $setoptLastOption;
	public $setoptLastValue;
	public $setoptFakeResult;
	public $execCalled = false;
	public $execLastSession;
	public $execFakeResult;
	public $closeCalled = false;
	public $closeLastSession;
	public $closeFakeResult;
	public $getinfoCalled = false;
	public $getinfoLastSession;
	public $getinfoLastOpt;
	public $getinfoFakeResult;

	public function init($url)
	{
		$this->initCalled = true;
		$this->initLastUrl = $url;
		return $this->initFakeResult;
	}

	public function setopt($session, $opt, $value)
	{
		$this->setoptCalled = true;
		$this->setoptLastOption[] = $opt;
		$this->setoptLastValue[] = $value;
		return $this->setoptFakeResult;
	}

	public function exec($session)
	{
		$this->execCalled = true;
		$this->execLastSession = $session;
		return $this->execFakeResult;
	}

	public function getinfo($session, $opt)
	{
		$this->getinfoCalled = true;
		$this->getinfoLastSession = $session;
		$this->getinfoLastOpt = $opt;
		return $this->getinfoFakeResult;
	}

	public function close($session)
	{
		$this->closeCalled = true;
		$this->closeLastSession = $session;
		return $this->closeFakeResult;
	}
}
