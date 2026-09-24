<?php

namespace Siberfx\CodelabStats;

/**
 * The reports the stats endpoint offers, and the values its options accept
 * (Developers → Stats in the CodeLabs Analytics docs).
 */
enum Report: string
{
    case Browser = 'browser';
    case Campaign = 'campaign';
    case City = 'city';
    case Continent = 'continent';
    case Country = 'country';
    case Device = 'device';
    case Event = 'event';
    case Language = 'language';
    case OperatingSystem = 'operating_system';
    case Page = 'page';
    case Pageview = 'pageview';
    case Referrer = 'referrer';
    case ScreenResolution = 'screen_resolution';
    case Visitor = 'visitor';

    public const array SEARCH_BY = ['value'];

    public const array SORT_BY = ['count', 'value'];

    public const array SORT = ['desc', 'asc'];

    public const array PER_PAGE = [10, 25, 50, 100];

    /** @return list<string> */
    public static function names(): array
    {
        return array_column(self::cases(), 'value');
    }
}
