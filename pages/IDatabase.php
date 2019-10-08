<?php

interface IDatabase
{
    function beginTransaction();
    function commit();
    function query(string $statement);
    function execute(string $statement, array $args);
    function getLastInsertId();
}
