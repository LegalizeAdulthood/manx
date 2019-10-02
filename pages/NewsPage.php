<?php

require_once 'PageBase.php';

class NewsPage extends PageBase
{
    protected function getMenuType()
    {
        return MenuType::News;
    }

    protected function renderBodyContent()
    {
        print <<<EOH
<h1>News</h1>

<h2>Version 2.0.7</h2>

<p>Eliminate duplication between bitsavers and ChiClassicComp front ends.
Use composer for dependency on PHPUnit, update to PHPUnit 6.</p>

<h2>Version 2.0.5</h2>

<p>Minor bug fixes and support for a ChiClassicComp front-end to the
URL Wizard.</p>

<h2>Version 2.0.4</h2>

<p>This is a minor update that incorporates various usability improvements
and bug fixes related to adding new documents.</p>

<h2>Version 2.0.3</h2>

<p>This second version of the open source manx application brings a number
of bug fixes, database corrections and new features.</p>

<p>The details page for
a publication now correctly reports the document title and the about and
help pages have been updated.</p>

<p>A new <a href="rss.php">RSS feed</a> allows users to know when new
publications are added to the database.</p>

<p>Registered users can add new publications and document copies to the
database through a URL Wizard.  The wizard automatically infers as much
information as possible from the document URL.  The BitSavers document
repository is given special treatment with a streamlined path through the
URL Wizard for new documents, in many cases automatically extracting enough
metadata from the document URL so that little manual editing or data entry
is required.</p>

<p>Documents originally hosted on vt100.net but now missing have been mirrored
onto manx and their URLs updated in the database.  DEC documents located at
ftp.digital.com have had their URLs corrected.  BitSavers is now mirroring
the content previously located in Wilber Williams' Computer Museum at the
University of Queensland, Australia.  The Harte, Antonio Carlini, hcps and
mds-199909 content that was previously mirrored at vt100.net is now mirrored
at manx and the URLs have been updated.</p>
EOH;

    }
}
