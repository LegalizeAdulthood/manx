<?php

const TIME_ZONE = 'America/Chicago';

interface IDateTimeProvider
{
    /**
     * @abstract
     * @return DateTime
     */
    function now();
}
