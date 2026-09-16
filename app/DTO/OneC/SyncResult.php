<?php

namespace App\DTO\OneC;

final class SyncResult
{
    public function __construct(
        public int $processed = 0,
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'processed' => $this->processed,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
