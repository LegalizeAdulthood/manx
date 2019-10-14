<?php

require_once 'Manx.php';
require_once 'AdminPageBase.php';

class CompanyPage extends AdminPageBase
{
    protected function getMenuType()
    {
        return MenuType::Company;
    }

    private function renderBeginForm($method, $heading = 'Edit Company')
    {
        print <<<EOH
<h1>$heading</h1>

<div id="compedit">
<form action="company.php" id="editform" method="$method">
<fieldset><legend>$heading</legend>
<ul>

EOH;
    }

    protected function renderBodyContent()
    {
        $id = $this->param('id');
        if (strlen($id) == 0)
        {
            $this->renderPickCompanyForm();
        }
        else if ($id != -1)
        {
            $company = $this->_manxDb->getCompanyForId($id);
            if (count($company))
            {
                $this->renderEditForm($company);
            }
            else
            {
                $this->renderPickCompanyForm();
            }
        }
        else
        {
            $this->renderAddCompanyForm();
        }
    }

    private function renderAddCompanyForm()
    {
        $this->renderBeginForm('post', 'Add Company');
        $company = array(
            'id' => -1,
            'name' => '',
            'short_name' => '',
            'sort_name' => '',
            'display' => true,
            'notes' => '');
        $this->renderFields($company);
        print <<<EOH
<input type="submit" name="opsave" value="Save" />
</form>
</div>

EOH;
    }

    private function renderFields($company)
    {
        $id = $company['id'];
        $name = $company['name'];
        $shortName = $company['short_name'];
        $sortName = $company['sort_name'];
        $display = $company['display'] ? ' checked="checked"' : '';
        $notes = $company['notes'];

        print <<<EOH
<li><label for="coname">Full name</label>
<input type="text" name="coname" value="$name" size="40" maxlength="50" /></li>
<li><label for="coshort">Short name or abbrev.</label>
<input type="text" name="coshort" value="$shortName" size="40" maxlength="50" /></li>
<li><label for="cosort">Name for sorting purposes</label>
<input type="text" name="cosort" value="$sortName" size="40" maxlength="50" /></li>
<li><label for="display">Displayed?</label>
<input type="checkbox" name="display"$display value="Y"/></li>
<li><label for="notes">Notes</label>
<input type="text" name="notes" value="$notes" size="40" maxlength="255" /></li>
</ul>
</fieldset>
<input type="hidden" name="id" value="$id" />

EOH;
    }

    private function renderEditForm($company)
    {
        $this->renderBeginForm('post');
        $this->renderFields($company);
        print <<<EOH
<input type="submit" name="opsave" value="Save" />
</form>
</div>

EOH;
    }

    private function renderPickCompanyForm()
    {
        $this->renderBeginForm('get');
        print <<<EOH
<li><label for="id">Full name</label>
<select id="id" name="id">
<option value="-1">(New Company)</option>

EOH;
        foreach ($this->_manxDb->getCompanyList() as $row)
        {
            $id = $row['id'];
            printf('<option value="%s">%s</option>' . "\n",
                $id, htmlspecialchars($row['name']));
        }
        print <<<EOH
</select>
</li>
</ul>
</fieldset>
<input type="submit" name="opedit" value="Edit" />
</form>
</div>

EOH;
    }

    protected function postPage()
    {
        $id = $this->_vars['id'];
        $fullName = $this->_vars['coname'];
        $shortName = $this->_vars['coshort'];
        $sortName = $this->_vars['cosort'];
        $display = $this->_vars['display'] == 'Y';
        $notes = $this->_vars['notes'];
        if ($id == -1)
        {
            $id = $this->_manxDb->addCompany($fullName, $shortName, $sortName, $display, $notes);
        }
        else
        {
            $this->_manxDb->updateCompany($id, $fullName, $shortName, $sortName, $display, $notes);
        }
        $this->redirect(sprintf("search.php?q=&cp=%d", $id));
    }
}
