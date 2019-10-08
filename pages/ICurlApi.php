<?php

interface ICurlApi
{
    public function init(string $url);
    public function setopt($session, $opt, $value);
    public function exec($session);
    public function getinfo($session, $opt);
    public function close($session);
}
