<?php

namespace Qik\Utility;

class qArray
{
    public static function IsAssociative($array)
    {
        if (array() === $array) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }
}