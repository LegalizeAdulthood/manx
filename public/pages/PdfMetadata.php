<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

class PdfMetadata implements IPdfMetadata
{
    const DEFAULT_MAX_BYTES = 4194304;
    const MAX_BYTES_PROPERTY = 'pdf_metadata_max_bytes';
    const STATUS_EMPTY = 'empty';
    const STATUS_NOT_PDF = 'not_pdf';
    const STATUS_OK = 'ok';
    const STATUS_TOO_LARGE = 'too_large';
    const TEMP_FILE_PREFIX = 'pdf-metadata-';

    public function __construct(
        IManxDatabase $db,
        IPdfMetadataParser $parser = null,
        \GuzzleHttp\ClientInterface $client = null,
        $tempDir = null)
    {
        $this->_db = $db;
        $this->_parser = is_null($parser) ? new PdfMetadataParser() : $parser;
        $this->_client = is_null($client)
            ? new \GuzzleHttp\Client(array('http_errors' => false))
            : $client;
        $tempFilePrefix = Config::configFile(self::TEMP_FILE_PREFIX);
        $this->_tempDir = is_null($tempDir) ? dirname($tempFilePrefix) : $tempDir;
        $this->_tempPrefix = basename($tempFilePrefix);
    }

    public function metadataForUrl($url)
    {
        if (!self::isPdfUrl($url))
        {
            return self::emptyResult(self::STATUS_NOT_PDF);
        }

        $maxBytes = $this->maxBytes();
        $contentLength = $this->contentLength($url);
        if (is_null($contentLength) || $contentLength > $maxBytes)
        {
            return self::emptyResult(self::STATUS_TOO_LARGE);
        }

        $tempFile = tempnam($this->_tempDir, $this->_tempPrefix);
        if ($tempFile === false)
        {
            return self::emptyResult(self::STATUS_EMPTY);
        }

        try
        {
            $downloadStatus = $this->download($url, $tempFile, $maxBytes);
            if ($downloadStatus != self::STATUS_OK)
            {
                return self::emptyResult($downloadStatus);
            }

            $metadata = $this->_parser->metadataForFile($tempFile);
            $status = self::metadataIsEmpty($metadata)
                ? self::STATUS_EMPTY
                : self::STATUS_OK;
            return array_merge(array('status' => $status), $metadata);
        }
        finally
        {
            if (is_string($tempFile) && file_exists($tempFile))
            {
                unlink($tempFile);
            }
        }
    }

    private function maxBytes()
    {
        $value = $this->_db->getProperty(self::MAX_BYTES_PROPERTY);
        if ($value === false || !ctype_digit((string)$value) || intval($value) < 1)
        {
            return self::DEFAULT_MAX_BYTES;
        }
        return intval($value);
    }

    private function contentLength($url)
    {
        try
        {
            $response = $this->_client->request('HEAD', $url, array(
                'allow_redirects' => true,
                'http_errors' => false
            ));
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e)
        {
            return null;
        }

        if ($response->getStatusCode() != 200)
        {
            return null;
        }

        $length = trim($response->getHeaderLine('Content-Length'));
        if ($length == '' || !ctype_digit($length))
        {
            return null;
        }

        return intval($length);
    }

    private function download($url, $tempFile, $maxBytes)
    {
        try
        {
            $response = $this->_client->request('GET', $url, array(
                'allow_redirects' => true,
                'http_errors' => false,
                'stream' => true
            ));
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e)
        {
            return self::STATUS_EMPTY;
        }

        if ($response->getStatusCode() != 200)
        {
            return self::STATUS_EMPTY;
        }

        $file = fopen($tempFile, 'wb');
        if ($file === false)
        {
            return self::STATUS_EMPTY;
        }

        $bytes = 0;
        $body = $response->getBody();
        try
        {
            while (!$body->eof())
            {
                $chunk = $body->read(8192);
                if ($chunk == '')
                {
                    break;
                }
                $bytes += strlen($chunk);
                if ($bytes > $maxBytes)
                {
                    return self::STATUS_TOO_LARGE;
                }
                if (fwrite($file, $chunk) !== strlen($chunk))
                {
                    return self::STATUS_EMPTY;
                }
            }
        }
        finally
        {
            fclose($file);
        }

        return self::STATUS_OK;
    }

    public static function emptyResult($status)
    {
        return array_merge(
            array('status' => $status),
            PdfMetadataParser::emptyMetadata());
    }

    private static function isPdfUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        return is_string($path) && strtolower(substr($path, -4)) == '.pdf';
    }

    private static function metadataIsEmpty(array $metadata)
    {
        foreach ($metadata as $value)
        {
            if ($value != '')
            {
                return false;
            }
        }
        return true;
    }

    /** @var \GuzzleHttp\ClientInterface */
    private $_client;
    /** @var IManxDatabase */
    private $_db;
    /** @var IPdfMetadataParser */
    private $_parser;
    private $_tempDir;
    private $_tempPrefix;
}
