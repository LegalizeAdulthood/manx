<?php

namespace Manx;

class Config
{
    static public function configFile($file)
    {
        return __DIR__ . '/../../private/' . $file;
    }
}
