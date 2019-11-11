<?php

interface IExclusiveLock
{
    function lock($name);
}
