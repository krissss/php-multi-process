<?php

namespace Kriss\MultiProcessTests\Fixtures;

class CallbackClass
{
    public static function getHostname()
    {
        return gethostname();
    }

    public static function getValue(string $value)
    {
        return $value;
    }

    public function getHostname2()
    {
        return gethostname();
    }
}