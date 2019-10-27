<?php

interface IWhatsNewCleaner
{
    function removeNonExistentUnknownPaths();
    function updateMovedFiles();
    function updateWhatsNewIndex();
    function removeUnknownPathsWithCopy();
}
