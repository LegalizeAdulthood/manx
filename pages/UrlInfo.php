<?php

require_once 'CurlApi.php';

class UrlInfo
{
	private $_api;
	private $_url;

	public function __construct($url, ICurlApi $api = null)
	{
		$this->_url = $url;
		$this->_api = is_null($api) ? CurlApi::getInstance() : $api;
	}

	public function size()
	{
		$session = $this->_api->init($this->_url);
		$this->_api->setopt($session, CURLOPT_URL, $this->_url);
		$this->_api->setopt($session, CURLOPT_HEADER, 1);
		$this->_api->setopt($session, CURLOPT_NOBODY, 1);
		$this->_api->setopt($session, CURLOPT_RETURNTRANSFER, 1);
		$this->_api->setopt($session, CURLOPT_FRESH_CONNECT, 1);
		$result = $this->_api->exec($session);
		if (!$result)
		{
			$this->_api->close($session);
			return false;
		}

		$httpStatus = $this->_api->getinfo($session, CURLINFO_HTTP_CODE);
		$size = 0;
		if ($httpStatus == 200)
		{
			$size = $this->getHeaderValue($result, 'content-length');
		}
		else if ($httpStatus == 302)
		{
			$url = $this->getHeaderValue($result, 'location');
			if ($url)
			{
				$this->_url = $url;
				$this->_api->close($session);
				return $this->size();
			}
		}
		$this->_api->close($session);
		return $size;
	}

	private function getHeaderValue($headers, $name)
	{
		foreach (explode("\n", str_replace("\r", '', $headers)) as $line)
		{
			if (strpos($line, ':') > 0)
			{
				list($header, $value) = explode(':', $line, 2);
				if (strtolower($header) == $name)
				{
					return trim($value);
				}
			}
		}
		return false;
	}

	public function md5()
	{
		$session = $this->_api->init($this->_url);
		$this->_api->setopt($session, CURLOPT_URL, $this->_url);
		$this->_api->setopt($session, CURLOPT_RETURNTRANSFER, 1);
		$result = $this->_api->exec($session);
		if (!$result)
		{
			$this->_api->close($session);
			return false;
		}

		$httpStatus = $this->_api->getinfo($session, CURLINFO_HTTP_CODE);
		$md5 = false;
		if ($httpStatus < 400)
		{
			$md5 = md5($result);
		}
		$this->_api->close($session);
		return array($httpStatus, $md5);
	}
}
