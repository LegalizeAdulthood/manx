<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

use Pimple\Container;

abstract class PageBase
{
    /** @var IManx */
    protected $_manx;
    /** @var IManxDatabase */
    protected $_manxDb;
    protected $_topDir;
    /** @var IUser */
    protected $_user;

    public function __construct(Container $config)
    {
        $manx = $config['manx'];
        $this->_manx = $manx;
        $this->_manxDb = $manx->getDatabase();
        $this->_topDir = array_key_exists('PATH_INFO', $_SERVER) ?
            str_repeat('../', count(explode('/', $_SERVER['PATH_INFO'])) - 1)
            : '';
        $this->_user = $manx->getUserFromSession();
    }

    public function __destruct()
    {
        $this->_manx = null;
    }

    public function renderPage()
    {
        $this->renderHeader();
        $this->renderBody();
    }

    protected function getTitle()
    {
        return "Manx";
    }

    protected function getMenuType()
    {
        return MenuType::Undefined;
    }

    protected function renderLink($rel, $type, $href, $attributes = array())
    {
        $attributeText = '';
        foreach ($attributes as $name => $value)
        {
            $attributeText .= sprintf('%s="%s" ', $name, htmlspecialchars(trim($value)));
        }
        printf('<link rel="%s" type="%s" href="%s" %s/>' . "\n", $rel, $type, $this->_topDir . $href, $attributeText);
    }

    protected function renderHeader()
    {
        header("Content-Type: text/html; charset=utf-8");
        print <<<EOH
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/tr/html4/strict.dtd">
<html lang="en">
<head>
EOH;
        printf("<title>%s</title>\n", $this->getTitle());
        $this->renderLink("stylesheet", "text/css", "assets/manx.css");
        $this->renderLink("shortcut icon", "image/x-icon", "assets/manx.ico");
        $this->renderLink("alternate", "application/rss+xml", "rss.php", array('title' => 'Manx New Documents'));
        $this->renderHeaderContent();
        print "</head>\n";
    }

    protected function renderHeaderContent()
    {
    }

    protected function renderBody()
    {
        $this->renderBodyHeader();
        $this->renderBodyContent();
        $this->renderBodyFooter();
    }

    abstract protected function renderBodyContent();

    private function renderFirstMenuItem($selected, $href, $text)
    {
        $this->renderMenuItemWithAttributes($selected, 'firston', 'class="first" ', $text, $href);
    }

    private function renderMenuItem($selected, $href, $text)
    {
        $this->renderMenuItemWithAttributes($selected, 'on', '', $text, $href);
    }

    private function renderMenuItemWithAttributes($selected, $selectedClass, $unselectedAttribute, $text, $href)
    {
        if ($selected)
        {
            printf('<a class="%s">%s</a>', $selectedClass, $text);
        }
        else
        {
            printf('<a %shref="%s">%s</a>', $unselectedAttribute, $this->_topDir . $href, $text);
        }
    }

    private function renderAdminMenu($menu)
    {
        if ($this->_user->isAdmin())
        {
            // Administrative tasks menu
            print "<div class=\"menu\">\n";
            $this->renderFirstMenuItem($menu == MenuType::UrlWizard, "url-wizard.php", "URL Wizard");
            $this->renderMenuItem($menu == MenuType::BitSavers, "whatsnew.php?site=bitsavers&parentDir=-1", "BitSavers");
            $this->renderMenuItem($menu == MenuType::ChiClassicComp, "whatsnew.php?site=ChiClassicComp&parentDir=-1", "ChiClassicComp");
            $this->renderMenuItem($menu == MenuType::SizeReport, "size-report.php", "Size Report");
            $this->renderMenuItem($menu == MenuType::MD5Report, "md5-report.php", "MD5 Report");
            print "</div>\n";

            // Entity menu
            print "<div class=\"menu\">\n";
            // $this->renderMenuItem($menu == MenuType::Company, "company.php", "Company");
            // $this->renderMenuItem($menu == MenuType::Publication, "publication.php", "Publication");
            // $this->renderMenuItem($menu == MenuType::Copy, "copy.php", "Copy");
            $this->renderFirstMenuItem($menu == MenuType::Site, "site.php", "Site");
            $this->renderMenuItem($menu == MenuType::Mirror, "mirror.php", "Mirror");
            print "</div>\n";
        }
    }

    protected function renderMenu()
    {
        $menu = $this->getMenuType();

        print '<div class="menu">';
        $this->renderFirstMenuItem(($menu == MenuType::Search), "search.php", "Search");
        $this->renderMenuItem(($menu == MenuType::News), "news.php", "News");
        $this->renderMenuItem(($menu == MenuType::About), "about.php", "About");
        $this->renderMenuItem(($menu == MenuType::Help), "help.php", "Help");
        printf('<a href="rss.php"><img style="vertical-align: middle" src="%sassets/rss.png"></a>', self::getRelativePrefixFromPathInfo());
        print "</div>\n";
        $this->renderAdminMenu($menu);
    }

    protected function renderBodyHeader()
    {
        print <<<EOH
<body id="VT100-NET">
<div id="HEADER">
EOH;
        $this->renderAuthorization();
        print <<<EOH
<div id="LOGO"><h1><span>Manx &ndash; a catalogue of online computer manuals</span></h1></div>
EOH;
        $this->renderMenu();
        print <<<EOH
</div>
<div class="det">
EOH;
    }

    protected function renderBodyFooter()
    {
        print <<<EOH
</div></body></html>
EOH;
    }

    protected function redirect($target)
    {
        header("Status: 303 See Also");
        header("Location: " . $target);
        header("Content-Type: text/plain");
        print "Redirecting to " . $target;
    }

    public static function getRelativePrefixFromPathInfo()
    {
        return array_key_exists('PATH_INFO', $_SERVER) ?
            str_repeat('../', count(explode('/', $_SERVER['PATH_INFO'])) - 1)
            : '';
    }

    public static function getAbsolutePrefixFromScriptName($server)
    {
        $parts = explode('/', $server['SCRIPT_NAME']);
        array_shift($parts);
        if (count($parts) > 1)
        {
            array_pop($parts);
            $prefix = implode('/', $parts) . '/';
        }
        else
        {
            $prefix = '';
        }
        return $prefix;
    }

    public function renderLoginLink($server)
    {
        $self = $server['PHP_SELF'];
        if (array_key_exists('QUERY_STRING', $server) and strlen($server['QUERY_STRING']) > 0)
        {
            $self = $self . '?' . $server['QUERY_STRING'];
        }
        if (preg_match('/\/login.php/', $self))
        {
            $redirect = $self;
        }
        else
        {
            $prefix = self::getAbsolutePrefixFromScriptName($server);
            $redirect = sprintf("/%slogin.php?redirect=%s", $prefix, urlencode($self));
        }
        printf('<a href="https://%s%s">Login</a>', $server['SERVER_NAME'], $redirect);
    }

    private function renderLogoutLink($server)
    {
        $prefix = self::getRelativePrefixFromPathInfo();
        $absolutePrefix = self::getAbsolutePrefixFromScriptName($server);
        printf('<a href="https://%s/%slogin.php?LOGO=1&redirect=%ssearch.php">Logout</a>',
            $server['SERVER_NAME'], $absolutePrefix, $prefix);
    }

    protected function renderAuthorization()
    {
        print "<div id=\"AUTH\"><table>\n<tr><td>" . $this->_user->displayName() . ' | ';
        if ($this->_user->isLoggedIn())
        {
            $this->renderLogoutLink($_SERVER);
        }
        else
        {
            $this->renderLoginLink($_SERVER);
        }
        printf("</td></tr>\n"
            . "<tr><td>&nbsp;</td></tr>\n"
            . "<tr><td class=\"version\" align=\"right\">version %s</td></tr>\n"
            . "</table></div>\n",
            $this->_manxDb->getManxVersion());
    }
}
