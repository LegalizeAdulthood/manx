<?php

require_once 'PageBase.php';

use Pimple\Container;

class LoginPage extends PageBase
{
    private $_loginFailed;
    private $_cookieFailed;

    public function __construct(Container $config)
    {
        parent::__construct($config);
        $this->_loginFailed = false;
        $this->_cookieFailed = false;
    }

    function getRedirect()
    {
        $redirect = '';
        if (array_key_exists('redirect', $_POST))
        {
            $redirect = $_POST['redirect'];
        }
        else if (array_key_exists('redirect', $_GET))
        {
            $redirect = $_GET['redirect'];
        }
        else
        {
            $redirect = "search.php?q=&cp=1";
        }
        return $redirect;
    }

    function renderBodyContent()
    {
        print <<<EOH
<form id="LOGINFORM" method="post" action="login.php">
<table id="LOGINBOX">
<tbody>
<tr><td><label for="USERFIELD">Email:</label></td>
<td><input type="text" id="USERFIELD" name="user" size="40" value="" /></td></tr>
<tr><td><label for="PASSFIELD">Password:</label></td>
<td><input type="password" id="PASSFIELD" name="pass" size="20" /></td></tr>
<tr><td colspan="2">
<input type="submit" id="LOGIBUTT" name="LOGI" value="Login" />
EOH;
        print '<input type="hidden" name="redirect" value="' . $this->getRedirect() . '" /></td></tr>';
        print "</tbody></table></form>\n";

        if ($this->_loginFailed)
        {
            print '<p style="color:red">Username or password incorrect</p>';
        }
        if ($this->_cookieFailed)
        {
            print '<p style="color:red">You need to enable cookies to login</p>';
        }
        print '</body></html>';
    }

    function renderPage()
    {
        $this->_loginFailed = false;
        if (array_key_exists('LOGO', $_GET))
        {
            $this->_manx->logout();
            if (array_key_exists('redirect', $_GET))
            {
                $this->redirect($_GET['redirect']);
                return;
            }
        }
        else if (array_key_exists('LOGI', $_POST))
        {
            if ($this->_manx->loginUser($_POST['user'], sha1($_POST['pass'])))
            {
                // Now take our cookie and redirect back to this script to test
                $this->redirect($_SERVER['REQUEST_URL'] . '?check=1&redirect=' . urlencode($this->getRedirect()));
                exit;
            }
            else
            {
                $this->_loginFailed = true;
            }
        }

        $this->_cookieFailed = false;
        if (array_key_exists('check', $_GET))
        {
            if (Cookie::get() != 'OUT')
            {
                $this->redirect($this->getRedirect());
                exit;
            }
            else
            {
                $this->_cookieFailed = true;
            }
        }

        parent::renderPage();
    }
}
