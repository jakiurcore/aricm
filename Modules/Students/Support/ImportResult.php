<?php

namespace Modules\Students\Support;

final readonly class ImportResult
{
    /**
     * @param  array<int, list<string>>  $errors  CSV line number => messages.
     */
    public function __construct(
        public int $valid,
        public int $created,
        public array $errors,
        public bool $dryRun,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['valid' => $this->valid, 'created' => $this->created, 'dry_run' => $this->dryRun, 'errors' => $this->errors];
    }
}
