<?php

namespace Manx;

class PdfMetadataCache
{
    public static function storeSiteUnknown(
        IManxDatabase $db, $unknownId, array $metadata)
    {
        $status = self::metadataStatus($metadata);
        $error = $status == 'error'
            ? self::metadataError($metadata)
            : '';
        $db->updateSiteUnknownPdfMetadata($unknownId,
            self::metadataValue($metadata, 'title', 255),
            self::metadataValue($metadata, 'keywords', 100),
            self::metadataValue($metadata, 'abstract', 2048),
            self::metadataValue($metadata, 'copy_notes', 200),
            self::metadataValue($metadata, 'copy_credits', 200),
            $status, $error);
    }

    private static function metadataStatus(array $metadata)
    {
        if (!array_key_exists('status', $metadata))
        {
            return 'error';
        }
        if ($metadata['status'] == PdfMetadata::STATUS_OK)
        {
            return 'ok';
        }
        if ($metadata['status'] == PdfMetadata::STATUS_EMPTY)
        {
            return 'none';
        }
        return 'error';
    }

    private static function metadataError(array $metadata)
    {
        if (array_key_exists('error', $metadata)
            && strlen($metadata['error']) > 0)
        {
            return self::truncate($metadata['error'], 255);
        }
        return self::truncate(
            array_key_exists('status', $metadata) ? $metadata['status'] : '',
            255);
    }

    private static function metadataValue(array $metadata, $key, $length)
    {
        return self::truncate(
            array_key_exists($key, $metadata) ? trim((string)$metadata[$key]) : '',
            $length);
    }

    private static function truncate($value, $length)
    {
        return substr($value, 0, $length);
    }
}
