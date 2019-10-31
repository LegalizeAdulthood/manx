<?php

require_once 'pages/User.php';

use Pimple\Container;

class IngestionRobotUser extends User
{
    public function __construct(Container $config)
    {
        $manx = $config['manx'];
        $db = $manx->getDatabase();
        $row = $db->getIngestionRobotUser();
        parent::__construct($row);
    }
}
