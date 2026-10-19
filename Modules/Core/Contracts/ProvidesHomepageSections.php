<?php

namespace Modules\Core\Contracts;

use Modules\Core\Support\HomepageSection;

/**
 * Implemented by modules that contribute a block to the public home page.
 * The Website module renders the blocks in sort order and never imports the providers.
 */
interface ProvidesHomepageSections
{
    /**
     * @return list<HomepageSection>
     */
    public function homepageSections(): array;
}
