<?php

require_once 'IDatabase.php';

class PDODatabaseAdapter implements IDatabase
{
    public static function getInstance()
    {
        $config = explode(" ", trim(file_get_contents("../../private/manx/config.txt")));
        $pdo = new PDO($config[0], $config[1], $config[2]);
        return new PDODatabaseAdapter($pdo);
    }
    private function __construct($pdo)
    {
        $this->_pdo = $pdo;
    }
    /** @var PDO */
    private $_pdo;

    public function query($statement)
    {
        return $this->_pdo->query($statement);
    }

    public function execute($statement, $args)
    {
        $prepared = $this->_pdo->prepare($statement);
        return $prepared->execute($args) ? $prepared->fetchAll() : array();
    }

    public function getLastInsertId()
    {
        return $this->_pdo->lastInsertId();
    }
}
