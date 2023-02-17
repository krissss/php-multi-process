<?php

namespace Kriss\MultiProcessTests;

class SymfonyConsoleTestClass
{
    public static function handle($param1, $param2)
    {
        return [
            'param1' => $param1,
            'param2' => $param2,
        ];
    }
}