<?php


namespace Jiaxincui\Throttler;

use Illuminate\Support\Facades\Facade;

class Throttler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ThrottlerManager::class;
    }
}
