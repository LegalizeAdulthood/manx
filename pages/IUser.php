<?php

namespace Manx;

interface IUser
{
    function isLoggedIn();
    function displayName();
    function isAdmin();
    function userId();
}
