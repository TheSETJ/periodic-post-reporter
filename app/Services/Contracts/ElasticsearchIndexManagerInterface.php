<?php

namespace App\Services\Contracts;

interface ElasticsearchIndexManagerInterface
{
    public function ensureIndexExists(string $index, array $mappings = [], array $settings = []): bool;
    public function bulkIndex(string $index, array $documents): bool;
}
