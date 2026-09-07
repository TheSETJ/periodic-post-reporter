<?php

namespace App\Services;

use App\Services\Contracts\ElasticsearchIndexManagerInterface;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;

class ElasticsearchIndexManager implements ElasticsearchIndexManagerInterface
{
    public function __construct(
        private Client $client
    ) {
    }

    public function ensureIndexExists(string $index, array $mappings = [], array $settings = []): bool
    {
        $params = ['index' => $index];

        if ($this->client->indices()->exists(['index' => $index])->asBool()) {
            return true;
        }

        if (!empty($mappings)) {
            $params['body']['mappings'] = $mappings;
        }

        if (!empty($settings)) {
            $params['body']['settings'] = $settings;
        }

        try {
            $response = $this->client->indices()->create($params);

            if ($response['errors'] ?? false) {
                Log::error('Elasticsearch index creation had failures', ['index' => $index, 'response' => $response]);

                return false;
            }
        } catch (\Throwable $e) {
            Log::error('Elasticsearch index creation thrown an exception', ['index' => $index, 'exception' => $e->getMessage()]);

            return false;
        }

        return true;
    }

    public function bulkIndex(string $index, array $documents): bool
    {
        $params = ['index' => $index, 'body' => $documents];

        try {
            $response = $this->client->bulk($params);

            if ($response['errors'] ?? false) {
                Log::error('Elasticsearch bulk insertion had failures', ['index' => $index, 'response' => $response]);

                return false;
            }
        } catch (\Throwable $e) {
            Log::error('Elasticsearch bulk insertion thrown an exception', ['index' => $index, 'exception' => $e->getMessage()]);

            return false;
        }

        return true;
    }
}
