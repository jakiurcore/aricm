<?php

namespace Modules\Admission\Services;

use Modules\Core\Services\Settings;

/**
 * The admission banner and page content: stored in the Core settings table under an "admission_" prefix.
 */
class AdmissionSettings
{
    public function __construct(protected Settings $settings) {}

    public function get(string $key): mixed
    {
        return $this->settings->get("admission_{$key}", config("modules.admission.defaults.{$key}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return collect(array_keys(config('modules.admission.defaults')))->mapWithKeys(fn (string $key): array => [$key => $this->get($key)])->all();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        $this->settings->setMany(collect($values)->only(array_keys(config('modules.admission.defaults')))->mapWithKeys(fn (mixed $value, string $key): array => ["admission_{$key}" => $value])->all());
    }

    /**
     * Localised text of a bn/en pair, falling back to Bangla.
     */
    public function text(string $key): string
    {
        return (string) (($this->get($key.'_'.app()->getLocale())) ?: $this->get($key.'_bn'));
    }

    /**
     * @return list<string>
     */
    public function classes(): array
    {
        return array_values(array_filter((array) $this->get('classes')));
    }

    public function bannerEnabled(): bool
    {
        return (bool) $this->get('banner_enabled');
    }
}
