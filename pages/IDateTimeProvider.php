<?php

namespace Manx;

const TIME_ZONE = 'America/Denver';

interface IDateTimeProvider
{
    /**
     * @abstract
     * @return DateTime
     */
    function now();
}
