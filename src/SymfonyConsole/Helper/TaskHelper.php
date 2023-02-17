<?php

namespace Kriss\MultiProcess\SymfonyConsole\Helper;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

class TaskHelper
{
    public static function encode($value): string
    {
        if ($value instanceof Closure) {
            $value = new SerializableClosure($value);
        }
        return base64_encode(serialize($value));
    }

    public static function decode($value)
    {
        return unserialize(base64_decode($value));
    }
}