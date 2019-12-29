<?php

namespace Manx;

require_once __DIR__ . '/../vendor/autoload.php';

class User implements IUser
{
    private $_userId;
    private $_loggedIn;
    private $_firstName;
    private $_lastName;
    private $_displayName;
    private $_admin;

    public static function getInstanceFromSession(IManxDatabase $manxDb)
    {
        $row = $manxDb->getUserFromSessionId(Cookie::get());
        if (array_key_exists('user_id', $row))
        {
            date_default_timezone_set(TIME_ZONE);
            if (time() - strtotime($row['last_impression']) > 30*60)
            {
                $manxDb->deleteUserSession(Cookie::get());
                Cookie::delete();
            }
        }
        else
        {
            $row['user_id'] = -1;
            $row['logged_in'] = 0;
            $row['first_name'] = 'Guest';
            $row['last_name'] = '';
        }
        return new User($row);
    }

    protected function __construct($row)
    {
        $this->_userId = $row['user_id'];
        $this->_loggedIn = $row['logged_in'] != 0;
        $this->_firstName = $row['first_name'];
        $this->_lastName = $row['last_name'];
        $this->_displayName = sprintf("%s %s", $this->_firstName, $this->_lastName);
        $this->_admin = $this->_loggedIn;
    }

    public function isAdmin()
    {
        return $this->_admin;
    }

    public function userId()
    {
        return $this->_userId;
    }

    public function isLoggedIn()
    {
        return $this->_loggedIn;
    }

    public function displayName()
    {
        return $this->_displayName;
    }
}
