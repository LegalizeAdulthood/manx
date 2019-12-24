<?php

namespace Manx;

require_once 'vendor/autoload.php';

$db = PDODatabaseAdapter::getInstance();
$manxDb = ManxDatabase::getInstanceForDatabase($db);

function hasNonEmptyKey($map, $name)
{
    return array_key_exists($name, $map) && (strlen($map[$name]) > 0);
}

if (hasNonEmptyKey($_POST, 'part') && hasNonEmptyKey($_POST, 'company'))
{
    header("Content-Type: text/xml; charset=utf-8");

    print <<<EOH
<?xml-stylesheet href="assets/PartLookup.css" type="text/css" ?>
<publist>
EOH;
    global $manxDb;
    foreach ($manxDb->getPublicationsForPartNumber($_POST['part'], $_POST['company']) as $row)
    {
        $pubId = $row['pub_id'];
        $part = htmlspecialchars($row['ph_part']);
        $title = htmlspecialchars($row['ph_title']);
        printf('<pub manxid="%s"><part>%s</part><title>%s</title></pub>', $pubId, $part, $title);
    }
    print "</publist>\n";
}
