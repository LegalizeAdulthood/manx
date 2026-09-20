<?php

namespace Manx\Cron;

interface IWhatsNewCleaner
{
    function updateMovedFiles();
    function updateWhatsNewIndex();
    function removeUnknownPathsWithCopy();
    function ingest();
    function computeMissingMD5();
    function cachePdfMetadata($timeLimitSeconds);
    function updateIgnoredUnknownDirs();
}
