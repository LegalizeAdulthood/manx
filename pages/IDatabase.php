<?php

interface IDatabase
{
    function beginTransaction();
    function commit();
    function query($statement);
    function execute($statement, $args);
    function getLastInsertId();
}
