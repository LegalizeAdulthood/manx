<?php

require_once __DIR__ . '/../vendor/autoload.php';

class UrlNormalizerTest extends PHPUnit\Framework\TestCase
{
    public function testRawHashInFilenameIsEncoded()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo/file#1.pdf';

        $this->assertEquals(
            'http://bitsavers.org/pdf/dec/foo/file%231.pdf',
            Manx\UrlNormalizer::normalizeCopyUrl($url));
    }

    public function testRawHashInDirectoryIsEncoded()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo#bar/file.pdf';

        $this->assertEquals(
            'http://bitsavers.org/pdf/dec/foo%23bar/file.pdf',
            Manx\UrlNormalizer::normalizeCopyUrl($url));
    }

    public function testRawReservedCharactersAreEncoded()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo & bar/file (1).pdf';

        $this->assertEquals(
            'http://bitsavers.org/pdf/dec/foo%20%26%20bar/file%20%281%29.pdf',
            Manx\UrlNormalizer::normalizeCopyUrl($url));
    }

    public function testExistingEscapesAreNotDoubleEncoded()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo%23bar/file%20%281%29.pdf';

        $this->assertEquals($url, Manx\UrlNormalizer::normalizeCopyUrl($url));
    }

    public function testLowercaseEscapesAreCanonicalized()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo%23bar/file%2f1.pdf';

        $this->assertEquals(
            'http://bitsavers.org/pdf/dec/foo%23bar/file%2F1.pdf',
            Manx\UrlNormalizer::normalizeCopyUrl($url));
    }

    public function testQueryStringIsPreserved()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo bar/file.pdf?download=1&x=2';

        $this->assertEquals(
            'http://bitsavers.org/pdf/dec/foo%20bar/file.pdf?download=1&x=2',
            Manx\UrlNormalizer::normalizeCopyUrl($url));
    }

    public function testPlusRelativeCopyPathIsPreserved()
    {
        $url = '+dec/foo bar/file#1.pdf';

        $this->assertEquals(
            '+dec/foo%20bar/file%231.pdf',
            Manx\UrlNormalizer::normalizeCopyUrl($url));
    }
}
