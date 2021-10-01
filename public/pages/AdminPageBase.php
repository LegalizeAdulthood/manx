<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

abstract class AdminPageBase extends PageBase
{
    protected $_vars;

    public function __construct($config)
    {
        parent::__construct($config);
        $this->_vars = $config['vars'];
    }

    protected function param($name, $defaultValue = '')
    {
        if (array_key_exists($name, $this->_vars))
        {
            return rawurldecode($this->_vars[$name]);
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

    public function renderPage()
    {
        if (!$this->_user->isLoggedIn())
        {
            $host = $_SERVER['SERVER_NAME'];
            $self = $_SERVER['PHP_SELF'];
            if (preg_match('/\/login\.php/', $self))
            {
                $redirect = $self;
            }
            else
            {
                $absolutePrefix = PageBase::getAbsolutePrefixFromScriptName($_SERVER);
                $redirect = sprintf("https://%s/%slogin.php?redirect=%s", $host, $absolutePrefix, urlencode($self));
            }
            $this->redirect($redirect);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $this->postPage();
            return;
        }

        parent::renderPage();
    }

    protected function getAttribute($name, $options)
    {
        return array_key_exists($name, $options) ?
            sprintf(' %s="%s"', $name, $options[$name]) : '';
    }

    protected function renderTextInput($label, $id, $options)
    {
        $className = $this->getAttribute('class', $options);
        $width = $this->getAttribute('size', $options);
        $maxLength = $this->getAttribute('maxlength', $options);
        $readOnly = array_key_exists('readonly', $options) && $options['readonly'] ?
            ' readonly="readonly"' : '';
        $value = array_key_exists('value', $options) ? $options['value'] : '';
        print <<<EOH
<li id="${id}_field"$className>
<label for="$id">$label</label>
<input type="text" id="$id" name="$id"$width$maxLength$readOnly value="$value" />

EOH;
        $hasHelp = array_key_exists('help', $options);
        if ($hasHelp)
        {
            print <<<EOH
<img id="${id}_help_button" src="assets/help.png" width="16" height="16" />

EOH;
        }
        if (array_key_exists('working', $options))
        {
            print "<span id=\"${id}_working\" class=\"hidden working\">Working...</span>\n";
        }
        if ($hasHelp)
        {
            $help = $options['help'];
            print <<<EOH
<div id="${id}_help" class="hidden">$help</div>

EOH;
        }
        print <<<EOH
<div id="${id}_error" class="error hidden"></div>

EOH;
        print <<<EOH
</li>


EOH;
    }

    protected function renderTextInputMaxSize($label, $id, $size, $maxLength, $help, $options = [])
    {
        $this->renderTextInput($label, $id,
            array_merge(['size' => $size, 'maxlength' => $maxLength,
            'help' => $help], $options));
    }

    protected abstract function postPage();
}
