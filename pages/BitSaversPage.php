<?php

require_once 'AdminPageBase.php';
require_once 'UrlInfo.php';
require_once 'UrlTransfer.php';

define('WHATS_NEW_FILE', '../private/bitsavers-WhatsNew.txt');
define('WHATS_NEW_URL', 'http://bitsavers.trailing-edge.com/pdf/Whatsnew.txt');
define('TIMESTAMP_PROPERTY', 'bitsavers_whats_new_timestamp');

class BitSaversPage extends AdminPageBase
{
	public function __construct($manx, $vars)
	{
		parent::__construct($manx, $vars);
		if ($this->needWhatsNewFile())
		{
			$this->getWhatsNewFile();
		}
	}

	private function needWhatsNewFile()
	{
		$timeStamp = $this->_manxDb->getProperty(TIMESTAMP_PROPERTY);
		if ($timeStamp === false)
		{
			return true;
		}
		$urlInfo = new UrlInfo(WHATS_NEW_URL);
		$lastModified = $urlInfo->lastModified();
		if ($lastModified === false)
		{
			$lastModified = time();
		}
		$this->_manxDb->setProperty(TIMESTAMP_PROPERTY, $lastModified);
		return $lastModified > $timeStamp;
	}

	private function getWhatsNewFile()
	{
		$transfer = new UrlTransfer(WHATS_NEW_URL);
		$transfer->get(WHATS_NEW_FILE);
		$this->_manxDb->setProperty(TIMESTAMP_PROPERTY, time());
	}

	private function parseWhatsNewFile()
	{
		if ($this->_manxDb->getBitSaversUnknownPathCount() >= 10)
		{
			return;
		}

		$whatsNew = fopen(WHATS_NEW_FILE, 'r');
		$this->skipHeader($whatsNew);
		$i = 0;
		while (!feof($whatsNew) && $i < 100)
		{
			$line = trim(fgets($whatsNew));
			if (strlen($line) && $this->pathUnknown($line))
			{
				$this->addUnknownPath($line);
				++$i;
			}
		}
		fclose($whatsNew);
	}

	private function skipHeader($whatsNew)
	{
		while (!feof($whatsNew))
		{
			if (strpos(trim(fgets($whatsNew)), '=======') === 0)
			{
				return;
			}
		}
	}

	private function pathUnknown($line)
	{
		return $this->_manxDb->copyExistsForUrl('http://bitsavers.org/pdf/' . $line) === false
			&& $this->_manxDb->bitSaversIgnoredPath($line) === false;
	}

	private function addUnknownPath($line)
	{
		$this->_manxDb->addBitSaversUnknownPath($line);
	}

	protected function getMenuType()
	{
		return MenuType::BitSavers;
	}

	protected function postPage()
	{
		$ignored = array();
		for ($i = 0; $i < 10; ++$i)
		{
			$key = sprintf('ignore%1$d', $i);
			if (array_key_exists($key, $this->_vars))
			{
				array_push($ignored, $this->_vars[$key]);
			}
		}
		if (count($ignored))
		{
			foreach ($ignored as $path)
			{
				$this->_manxDb->ignoreBitSaversPath($path);
			}
		}
		PageBase::renderPage();
	}

	protected function renderBodyContent()
	{
		$this->parseWhatsNewFile();
		print <<<EOH
<h1>New BitSavers Publications</h1>

<form action="bitsavers.php" method="POST">
<ol>

EOH;
		$unknownPaths = $this->_manxDb->getBitSaversUnknownPaths();
		$num = min(10, count($unknownPaths));
		for ($i = 0; $i < $num; ++$i)
		{
			$path = $unknownPaths[$i]['path'];
			printf('<li><input type="checkbox" id="ignore%1$d" name="ignore%1$d" value="%2$s" />' . "\n", $i, $path);
			printf('<a href="url-wizard.php?url=http://bitsavers.trailing-edge.com/pdf/%1$s">%1$s</a></li>' . "\n", trim($path));
		}
		print <<<EOH
</ol>
<input type="submit" value="Ignore" />
</form>

EOH;

	}
}
