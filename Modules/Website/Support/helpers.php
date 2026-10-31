<?php

use Modules\Website\Services\SiteContent;

if (! function_exists('site_content')) {
    /**
     * The editable public-site content (texts, images, lists, prices); see SiteContent.
     */
    function site_content(): SiteContent
    {
        return app(SiteContent::class);
    }
}
