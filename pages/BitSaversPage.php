<?php

require_once 'AdminPageBase.php';
require_once 'UrlInfo.php';
require_once 'UrlTransfer.php';

define('IGNORED_FILE', '../private/bitsavers-ignored.txt');
define('WHATS_NEW_FILE', '../private/bitsavers-WhatsNew.txt');
define('WHATS_NEW_URL', 'http://bitsavers.trailing-edge.com/pdf/Whatsnew.txt');

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

	protected function getMenuType()
	{
		return MenuType::BitSavers;
	}

	private function needWhatsNewFile()
	{
		if (!file_exists(WHATS_NEW_FILE))
		{
			return true;
		}
		$urlInfo = new UrlInfo(WHATS_NEW_URL);
		$lastModified = $urlInfo->lastModified();
		return $lastModified > filemtime(WHATS_NEW_FILE);
	}

	private function getWhatsNewFile()
	{
		$transfer = new UrlTransfer(WHATS_NEW_URL);
		$transfer->get(WHATS_NEW_FILE);
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
			$stream = fopen(IGNORED_FILE, 'w+');
			foreach ($ignored as $url)
			{
				fprintf($stream, '%1$s' . "\n", $url);
			}
			fclose($stream);
		}
		PageBase::renderPage();
	}

	protected function renderBodyContent()
	{
		print <<<EOH
<h1>New BitSavers Publications</h1>

<form action="bitsavers.php" method="POST">
<ol>

EOH;
		$ignored = file_exists(IGNORED_FILE) ?
			explode("\n", str_replace("\r", '', file_get_contents(IGNORED_FILE))) : array();
		$whatsNew = fopen(WHATS_NEW_FILE, 'r');
		$this->skipHeader($whatsNew);
		$i = 0;
		while ($i < 10)
		{
			$line = trim(fgets($whatsNew));
			if (strlen($line) && !in_array($line, $ignored)
				&& $this->copyUnknown($line))
			{
				printf('<li><input type="checkbox" id="ignore%1$d" name="ignore%1$d" value="%2$s" />' . "\n", $i, $line);
				printf('<a href="url-wizard.php?url=http://bitsavers.trailing-edge.com/pdf/%1$s">%1$s</a></li>' . "\n", trim($line));
				++$i;
			}
		}
		print <<<EOH
</ol>
<input type="submit" value="Ignore" />
</form>

EOH;

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

	private function copyUnknown($line)
	{
		return $this->_manxDb->copyExistsForUrl('http://bitsavers.org/pdf/' . $line) === false;
	}
}
?>
