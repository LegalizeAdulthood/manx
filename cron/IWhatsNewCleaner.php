<?php

interface IWhatsNewCleaner
{
    function removeNonExistentUnknownPaths();
    function updateMovedFiles();
    function updateWhatsNewIndex();
    function removeUnknownPathsWithCopy();
    function ingest();
    function computeMissingMD5();
}
