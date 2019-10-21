<?php

require_once 'IManx.php';

use Pimple\Container;

abstract class ServicePageBase
{
    /** @var \IManx */
    protected $_manx;
    protected $_topDir;
    /** @var \IUser */
    protected $_user;
    protected $_vars;
    /** @var \IManxDatabase */
    protected $_db;

    public function __construct(Container $config)
    {
        $this->_manx = $config['manx'];
        $this->_topDir = array_key_exists('PATH_INFO', $_SERVER) ?
            str_repeat('../', count(explode('/', $_SERVER['PATH_INFO'])) - 1) : '';
        $this->_user = $this->_manx->getUserFromSession();
        $this->_vars = $config['vars'];
        $this->_db = $this->_manx->getDatabase();
    }

    public function __destruct()
    {
        $this->_manx = null;
        $this->_user = null;
    }

    protected function redirect($target)
    {
        header("Status: 303 See Also");
        header("Location: " . $target);
        header("Content-Type: text/plain");
        print "Redirecting to " . $target;
    }

    protected function param($name, $defaultValue = '')
    {
        if (array_key_exists($name, $this->_vars))
        {
            return $this->_vars[$name];
        }
        else
        {
            return $defaultValue;
        }
    }

    protected function quotedParam($name)
    {
        return htmlspecialchars($this->param($name));
    }

    public function processRequest()
    {
        if (!$this->_user->isLoggedIn())
        {
            header("Status: 401 Not Authorized");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            if ($this->methodDispatch())
            {
                return;
            }
        }

        $this->redirect("search.php");
    }

    protected abstract function methodDispatch();
}
