<?php

namespace Manx\Test;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function expectOutputStringIgnoringLineEndings(string $expectedString): void
    {
        $expectedString = str_replace("\r\n", "\n", $expectedString);
        $pattern = str_replace("\n", '\\R', preg_quote($expectedString, '/'));
        $this->expectOutputRegex('/\A' . $pattern . '\z/');
    }
}
