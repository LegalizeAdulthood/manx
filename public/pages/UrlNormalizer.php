<?php

namespace Manx;

class UrlNormalizer
{
    public static function normalize($url)
    {
        if (!is_string($url) || $url == '')
        {
            return $url;
        }

        $prefix = '';
        if (substr($url, 0, 1) == '+')
        {
            $prefix = '+';
            $url = substr($url, 1);
        }

        list($location, $query) = self::splitQuery($url);
        list($urlPrefix, $path) = self::splitPath($location);
        if ($path == '')
        {
            return $prefix . $location . $query;
        }
        return $prefix . $urlPrefix . self::encodePath($path) . $query;
    }

    private static function splitQuery($url)
    {
        $queryStart = strpos($url, '?');
        if ($queryStart === false)
        {
            return array($url, '');
        }
        return array(substr($url, 0, $queryStart), substr($url, $queryStart));
    }

    private static function splitPath($url)
    {
        if (substr($url, 0, 2) == '//')
        {
            return self::splitAbsolutePath($url, 2);
        }

        $schemeEnd = strpos($url, '://');
        if ($schemeEnd !== false)
        {
            return self::splitAbsolutePath($url, $schemeEnd + 3);
        }

        return array('', $url);
    }

    private static function splitAbsolutePath($url, $hostStart)
    {
        $pathStart = strpos($url, '/', $hostStart);
        if ($pathStart === false)
        {
            return array($url, '');
        }
        return array(substr($url, 0, $pathStart), substr($url, $pathStart));
    }

    private static function encodePath($path)
    {
        $encoded = '';
        for ($i = 0; $i < strlen($path);)
        {
            $ch = substr($path, $i, 1);
            if (self::isEncodedByte($path, $i))
            {
                $encoded .= '%' . strtoupper(substr($path, $i + 1, 2));
                $i += 3;
            }
            else if (self::isUnreservedPathByte($ch))
            {
                $encoded .= $ch;
                ++$i;
            }
            else
            {
                $encoded .= sprintf('%%%02X', ord($ch));
                ++$i;
            }
        }
        return $encoded;
    }

    private static function isEncodedByte($path, $index)
    {
        return substr($path, $index, 1) == '%'
            && $index + 2 < strlen($path)
            && preg_match('/^[0-9A-Fa-f][0-9A-Fa-f]$/',
                substr($path, $index + 1, 2)) == 1;
    }

    private static function isUnreservedPathByte($ch)
    {
        return preg_match('/^[-A-Za-z0-9._~\/]$/', $ch) == 1;
    }
}
