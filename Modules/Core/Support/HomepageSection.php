<?php

namespace Modules\Core\Support;

final readonly class HomepageSection
{
    /**
     * @param  string  $view  Namespaced Blade view, e.g. "notices::home.latest".
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $view,
        public array $data = [],
        public int $sortOrder = 0,
    ) {}
}
