<?php

require_once 'vendor/autoload.php';

require_once 'IDateTimeProvider.php';
require_once 'ManxDatabase.php';
require_once 'User.php';

class Manx implements Manx\IManx
{
    private $_manxDb;

    public static function getInstance()
    {
        $db = Manx\PDODatabaseAdapter::getInstance();
        /** @var $manxDb Manx\IManxDatabase */
        $manxDb = ManxDatabase::getInstanceForDatabase($db);
        return Manx::getInstanceForDatabase($manxDb);
    }
    public static function getInstanceForDatabase(Manx\IManxDatabase $db)
    {
        return new Manx($db);
    }
    protected function __construct(Manx\IManxDatabase $manxDb)
    {
        $this->_manxDb = $manxDb;
    }

    public function __destruct()
    {
        $this->_manxDb = null;
    }

    public function getDatabase()
    {
        return $this->_manxDb;
    }

    private function generateSessionId()
    {
        date_default_timezone_set(Manx\TIME_ZONE);
        return sprintf("%s.%06d",
            strftime("%Y%m%d%H%M%S", time()),
            rand(0, 1000000));
    }

    function logout()
    {
        $this->_manxDb->deleteUserSession(Manx\Cookie::get());
        Manx\Cookie::delete();
    }

    function loginUser($user, $password)
    {
        $userId = $this->_manxDb->getUserId($user, $password);
        if ($userId > 0)
        {
            $sessionId = $this->generateSessionId();
            $remoteHost = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $this->_manxDb->createSessionForUser($userId, $sessionId, $remoteHost, $userAgent);
            Manx\Cookie::set($sessionId);
            return true;
        }
        return false;
    }

    function getUserFromSession()
    {
        return User::getInstanceFromSession($this->_manxDb);
    }

    public function addPublication($user, $company, $part, $pubDate, $title,
        $publicationType, $altPart, $revision, $keywords, $notes, $abstract,
        $languages)
    {
        $pubHistoryId = $this->_manxDb->addPubHistory($user->userId(),
            $publicationType, $company, $part, $altPart, $revision, $pubDate,
            $title, $keywords, $notes, $abstract, $languages);
        $pubId = $this->_manxDb->addPublication($pubHistoryId);
        $this->_manxDb->updatePubHistoryPubId($pubHistoryId, $pubId);
        return $pubId;
    }
}
