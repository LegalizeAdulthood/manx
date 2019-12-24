<?php

namespace Manx;

require_once 'pages/User.php';

use Pimple\Container;

class IngestionRobotUser extends User
{
    public function __construct(Container $config)
    {
        $manx = $config['manx'];
        $db = $manx->getDatabase();
        $userId = $db->getIngestionRobotUser();
        $row = [
            'user_id' => $userId,
            'first_name' => 'Ingestion',
            'last_name' => 'Robot',
            'logged_in' => 0
        ];
        parent::__construct($row);
    }
}
