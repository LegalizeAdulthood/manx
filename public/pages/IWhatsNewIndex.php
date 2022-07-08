<?php

namespace Manx;

interface IWhatsNewIndex
{
    function needIndexByDateFile();
    function getIndexByDateFile();
    function parseIndexByDateFile();
}
