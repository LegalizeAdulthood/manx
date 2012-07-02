<?php

require_once 'PageBase.php';
require_once 'RssWriter.php';
require_once 'IDateTimeProvider.php';

class RssPage extends PageBase
{
	private $_rss;

	public function __construct(IManx $manx, IDateTimeProvider $dateTimeProvider)
	{
		parent::__construct($manx);
		$this->_rss = new RssWriter($dateTimeProvider);
		$this->_rss->beginChannel('New Documents on Manx', 'http://manx.classiccmp.org',
				'A list of the most recently created documents in the Manx database.')
			->language('en-us');
		foreach ($this->_manxDb->getMostRecentDocuments(200) as $doc)
		{
			$title = $doc['ph_title'];
			$link = sprintf('details.php/%d,%d', $doc['ph_company'], $doc['ph_pub']);
			$description = $doc['ph_abstract'];
			if (strlen($description) == 0)
			{
				$description = $title;
			}
			$pubDate = new DateTime($doc['ph_created']);
			$this->_rss->item($title, $link, $description,
				array(
					'pubDate' => $pubDate->format(DateTime::RFC1123),
					'category' => $doc['company_name']
				));
		}
	}

	protected function renderHeader()
	{
		$this->_rss->renderHeader();
	}

	protected function renderBody()
	{
		$this->_rss->renderBody();
	}

	protected function renderBodyContent()
	{
		throw new Exception("renderBodyContent not used");
	}
}

?>
