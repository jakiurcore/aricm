<?php

namespace Modules\Website\Models\Concerns;

use Modules\Website\Services\SiteContent;

/**
 * Busts the cached site content whenever a row is written, so no write path can forget to.
 */
trait FlushesSiteContent
{
    protected static function bootFlushesSiteContent(): void
    {
        $flush = static fn () => app(SiteContent::class)->flush();

        static::saved($flush);
        static::deleted($flush);
    }
}
