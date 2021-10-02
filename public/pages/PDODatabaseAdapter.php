<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

class PDODatabaseAdapter implements IDatabase
{
    public static function getInstance()
    {
        $config = explode(" ", trim(file_get_contents(Config::configDir() . "config.txt")));
        $pdo = new \PDO($config[0], $config[1], $config[2]);
        return new PDODatabaseAdapter($pdo);
    }
    private function __construct($pdo)
    {
        $this->_pdo = $pdo;
    }
    /** @var \PDO */
    private $_pdo;

    public function beginTransaction()
    {
        $this->_pdo->beginTransaction();
    }

    public function commit()
    {
        $this->_pdo->commit();
    }

    public function query($statement)
    {
        return $this->_pdo->query($statement);
    }

    public function execute($statement, array $args)
    {
        $prepared = $this->_pdo->prepare($statement);
        return $prepared->execute($args) ? $prepared->fetchAll(\PDO::FETCH_ASSOC) : array();
    }

    public function getLastInsertId()
    {
        return $this->_pdo->lastInsertId();
    }
}
