<?php

interface IDatabase
{
    function beginTransaction();
    function commit();
    function query($statement);
    function execute($statement, array $args);
    function getLastInsertId();
}
