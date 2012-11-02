<?php

require_once 'AdminPageBase.php';
require_once 'UrlInfo.php';

class SizeReportPage extends AdminPageBase
{
	protected function getMenuType()
	{
		return MenuType::SizeReport;
	}

	protected function postPage()
	{
		PageBase::renderPage();
	}

	protected function renderBodyContent()
	{
		if (array_key_exists('operation', $this->_vars) && $this->_vars['operation'] == 'repair')
		{
			$this->renderBodyContentRepair();
		}
		else
		{
			$this->renderBodyContentReport();
		}
	}

	protected function renderBodyContentRepair()
	{
		print <<<EOH
<h1>Zero Size Repair Report</h1>

<p>
<table>
<tr><th>Publication</th><th>Size</th></tr>

EOH;
		foreach (array_keys($this->_vars) as $key)
		{
			if (strpos($key, 'row') === 0)
			{
				$ignore = str_replace('row', 'ignore', $key);
				list($copyId, $companyId, $pubId, $title) = explode(',', $this->_vars[$key]);
				$result = (array_key_exists($ignore, $this->_vars) && ($this->_vars[$ignore] == 1)) ?
					$this->ignoreSizeForCopy($copyId) : $this->updateSizeForCopy($copyId);
				printf('<tr><td><a href="details.php/%d,%d">%s</a></td><td>%s</td></tr>' . "\n",
					$companyId, $pubId, $title, $result);
			}
		}
		print <<<EOH
</table>
</p>

<p>
<div id="form_container">
<form id="size-report" action="size-report.php" method="POST" name="f">
<input type="submit" value="Report" />
</form>
</div>
</p>

EOH;
	}

	private function ignoreSizeForCopy($copyId)
	{
		$this->_manxDb->updateSizeForCopy($copyId, -1);
		return '(ignored)';
	}

	private function updateSizeForCopy($copyId)
	{
		$size = $this->getSizeForCopy($copyId);
		$this->_manxDb->updateSizeForCopy($copyId, $size);
		return $size;
	}

	private function getSizeForCopy($copyId)
	{
		foreach ($this->getUrlsForCopy($copyId) as $url)
		{
			$urlInfo = new UrlInfo($url);
			$size = $urlInfo->size();
			if ($size > 0)
			{
				return $size;
			}
		}
		return -1;
	}

	private function getUrlsForCopy($copyId)
	{
		return array_merge(array($this->_manxDb->getUrlForCopy($copyId)),
			$this->_manxDb->getMirrorsForCopy($copyId));
	}

	protected function renderBodyContentReport()
	{
		print <<<EOH
<h1>Zero Size Report</h1>


EOH;
		$rows = $this->_manxDb->getZeroSizeDocuments();
		if (count($rows) == 0)
		{
			print "<p><strong>No zero length documents found.</strong></p>\n";
		}
		else
		{
			print <<<EOH
<div id="form_container">
<form id="size-report" action="size-report.php" method="POST" name="f">

<ol>

EOH;
			$i = 0;
			foreach ($rows as $row)
			{
				printf('<li><input type="checkbox" id="ignore%1$d" name="ignore%1$d" value="1" />' . "\n", $i);
				printf('<a href="details.php/%1$d,%2$d">%3$s</a>' . "\n",
					$row['ph_company'], $row['ph_pub'], htmlspecialchars($row['ph_title']));
				printf('<input type="hidden" id="row%1$d" name="row%1$d" value="%2$d,%3$d,%4$d,%5$s" />' . "\n",
					$i, $row['copyid'], $row['ph_company'], $row['ph_pub'], htmlspecialchars($row['ph_title']));
				print "</li>\n";
				++$i;
			}
			print <<<EOH
</ol>
<input type="hidden" name="operation" value="repair" />
<input type="submit" value="Ignore Checked and Repair" />
</form>
</div>

EOH;
		}
	}
}
