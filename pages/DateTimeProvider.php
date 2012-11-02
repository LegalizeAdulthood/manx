<?php

require_once 'IDateTimeProvider.php';

class DateTimeProvider implements IDateTimeProvider
{
    public function now()
    {
        return new DateTime();
    }
}
