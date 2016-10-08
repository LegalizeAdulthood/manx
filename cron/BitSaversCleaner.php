<?php

require_once 'pages/IManx.php';

class BitSaversCleaner
{
    private $_manx;
    private $_db;
    private $_factory;

    public function __construct(IManx $manx, IBitSaversPageFactory $factory)
    {
        $this->_manx = $manx;
        $this->_db = $manx->getDatabase();
        $this->_factory = $factory;
    }

    public function removeNonExistentUnknownPaths()
    {
        foreach($this->_db->getAllBitSaversUnknownPaths() as $row)
        {
            $urlInfo = $this->_factory->createUrlInfo('http://bitsavers.org/pdf/' . $row['path']);
            if ($urlInfo->httpStatus() === 404)
            {
                $this->_db->removeBitSaversUnknownPathById($row['id']);
            }
        }
    }
}
