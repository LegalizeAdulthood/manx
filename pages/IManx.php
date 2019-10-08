<?php

interface IManx
{
    public function getDatabase();
    public function loginUser(string $user, string $password);
    public function logout();
    public function getUserFromSession();
    public function addPublication($user, $company, $part, $pubDate, $title,
        $publicationType, $altPart, $revision, $keywords, $notes, $abstract,
        $languages);
}
