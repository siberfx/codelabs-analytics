<?php

namespace Siberfx\CodelabStats\Exceptions;

use RuntimeException;

class MissingConfiguration extends RuntimeException
{
    public static function key(): self
    {
        return new self('CodeLabs Analytics API key is not set. Add CODELAB_STATS_KEY to your .env file.');
    }

    public static function websiteId(): self
    {
        return new self('CodeLabs Analytics website ID is not set. Add CODELAB_STATS_WEBSITE_ID to your .env file, or call forWebsite().');
    }
}
