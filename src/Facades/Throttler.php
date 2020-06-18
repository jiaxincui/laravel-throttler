<?php


namespace Jiaxincui\Throttler\Facades;

use Illuminate\Support\Facades\Facade;

class Throttler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Jiaxincui\Throttler\Throttler::class;
    }
}
