<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

class PdfMetadataParser implements IPdfMetadataParser
{
    public function metadataForFile($fileName)
    {
        try
        {
            $details = $this->_parser->parseFile($fileName)->getDetails();
        }
        catch (\Throwable $e)
        {
            return self::emptyMetadata();
        }

        return array(
            'title' => self::firstDetail($details, array('Title', 'dc:title')),
            'keywords' => self::firstDetail($details, array('Keywords', 'pdf:Keywords', 'dc:subject')),
            'abstract' => self::firstDetail($details, array('Subject', 'Description', 'dc:description')),
            'copy_notes' => self::firstDetail($details, array('Creator', 'xmp:CreatorTool', 'Producer', 'pdf:Producer')),
            'copy_credits' => self::firstDetail($details, array('Author', 'dc:creator'))
        );
    }

    public function __construct(\Smalot\PdfParser\Parser $parser = null)
    {
        $this->_parser = is_null($parser) ? new \Smalot\PdfParser\Parser() : $parser;
    }

    public static function emptyMetadata()
    {
        return array(
            'title' => '',
            'keywords' => '',
            'abstract' => '',
            'copy_notes' => '',
            'copy_credits' => ''
        );
    }

    private static function firstDetail(array $details, array $keys)
    {
        foreach ($keys as $key)
        {
            if (array_key_exists($key, $details))
            {
                $value = self::detailValue($details[$key]);
                if ($value != '')
                {
                    return $value;
                }
            }
        }
        return '';
    }

    private static function detailValue($value)
    {
        if (is_array($value))
        {
            $values = array();
            foreach ($value as $element)
            {
                $elementValue = self::detailValue($element);
                if ($elementValue != '')
                {
                    array_push($values, $elementValue);
                }
            }
            return implode(', ', $values);
        }

        if (is_scalar($value))
        {
            return trim((string)$value);
        }

        return '';
    }

    /** @var \Smalot\PdfParser\Parser */
    private $_parser;
}
