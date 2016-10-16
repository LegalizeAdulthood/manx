<?php

require_once 'pages/UrlWizardService.php';

class UrlWizardServiceTester extends UrlWizardService
{
    public function renderBodyContent()
    {
        parent::renderBodyContent();
    }

    protected function redirect($target)
    {
        $this->redirectCalled = true;
        $this->redirectLastTarget = $target;
    }
    public $redirectCalled, $redirectLastTarget;

    public function postPage()
    {
        parent::postPage();
    }

    protected function header($field)
    {
        $this->headerCalled = true;
        $this->headerLastField = $field;
    }
    public $headerCalled, $headerLastField;
}
