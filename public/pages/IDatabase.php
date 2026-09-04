<?php

namespace Manx;

interface IDatabase
{
    function beginTransaction();
    function commit();
    function rollback();
    function query($statement);
    function execute($statement, array $args);
    function getLastInsertId();
}
