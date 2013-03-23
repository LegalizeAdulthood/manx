<?php

class Cookie
{
    const NAME = 'manxSession';

    public static function get()
    {
        return array_key_exists(Cookie::NAME, $_COOKIE)
            ? $_COOKIE[Cookie::NAME] : '';
    }

    public static function set($value)
    {
        setcookie(Cookie::NAME, $value);
    }

    public static function delete()
    {
        date_default_timezone_set(TIME_ZONE);
        setcookie(Cookie::NAME, 'OUT', time() - 60);
    }
}
