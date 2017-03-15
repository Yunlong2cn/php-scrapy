<?php
namespace yunlong2cn\spider;


class Help
{
    public static function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
    }
}