<?php

namespace Siberfx\CodelabStats\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Siberfx\CodelabStats\CodelabStats forWebsite(int|string $websiteId)
 * @method static string websiteId()
 * @method static array websites(array $params = [])
 * @method static array stats(\Siberfx\CodelabStats\Report|string $name, \DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array summary(\DateTimeInterface|string $from, \DateTimeInterface|string $to)
 * @method static array browsers(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array campaigns(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array cities(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array continents(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array countries(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array devices(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array events(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array languages(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array operatingSystems(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array pages(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array pageviews(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array referrers(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array screenResolutions(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
 * @method static array visitors(\DateTimeInterface|string $from, \DateTimeInterface|string $to, array $params = [])
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
