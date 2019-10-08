<?php

require_once 'PDODatabaseAdapter.php';
require_once 'IDateTimeProvider.php';
require_once 'ManxDatabase.php';
require_once 'IManx.php';
require_once 'User.php';
require_once 'Cookie.php';

class Manx implements IManx
{
    private $_manxDb;

    public static function getInstance()
    {
        $db = PDODatabaseAdapter::getInstance();
        /** @var $manxDb IManxDatabase */
        $manxDb = ManxDatabase::getInstanceForDatabase($db);
        return Manx::getInstanceForDatabase($manxDb);
    }
    public static function getInstanceForDatabase(IManxDatabase $db)
    {
        return new Manx($db);
    }
    protected function __construct(IManxDatabase $manxDb)
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
        date_default_timezone_set(TIME_ZONE);
        return sprintf("%s.%06d",
            strftime("%Y%m%d%H%M%S", time()),
            rand(0, 1000000));
    }

    function logout()
    {
        $this->_manxDb->deleteUserSession(Cookie::get());
        Cookie::delete();
    }

    function loginUser(string $user, string $password)
    {
        $userId = $this->_manxDb->getUserId($user, $password);
        if ($userId > 0)
        {
            $sessionId = $this->generateSessionId();
            $remoteHost = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $this->_manxDb->createSessionForUser($userId, $sessionId, $remoteHost, $userAgent);
            Cookie::set($sessionId);
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
