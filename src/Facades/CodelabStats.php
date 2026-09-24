<?php

namespace Siberfx\CodelabStats\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Siberfx\CodelabStats\CodelabStats forWebsite(int|string $websiteId)
 * @method static string websiteId()
 * @method static array websites(array $params = [])
 * @method static array stats(string $name, \DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array summary(\DateTimeInterface|string $from, \DateTimeInterface|string $to)
 *
 * @see \Siberfx\CodelabStats\CodelabStats
 */
class CodelabStats extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Siberfx\CodelabStats\CodelabStats::class;
    }
}
