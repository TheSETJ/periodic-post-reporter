<?php

namespace App\Repositories;

use App\Repositories\Contracts\PostSearchRepositoryInterface;
use Carbon\Carbon;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Collection;

class ElasticsearchPostRepository implements PostSearchRepositoryInterface
{
    public function __construct(
        private Client $client
    ) {
    }

    public function search(array $keywords, Carbon $start, Carbon $end): Collection
    {
        $response = $this->client->search([
            'index' => 'posts_index',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'multi_match' => [
                                    'query' => implode(' ', $keywords),
                                    'fields' => ['title', 'content', 'lead'],
                                ]
                            ],
                        ],
                        'should' => [
                            ['terms' => ['tags' => $keywords]],
                            ['terms' => ['categories' => $keywords]],
                        ],
                        'filter' => [
                            [
                                'range' => [
                                    'published_at' => [
                                        'gte' => $start->toIso8601String(),
                                        'lte' => $end->toIso8601String(),
                                    ],
                                ]
                            ],
                        ]
                    ],
                ],
            ],
        ]);

        return collect($response['hits']['hits'])->map(fn($hit) => $hit['_source']);
    }

    public function countByDay(array $keywords, Carbon $start, Carbon $end): Collection
    {
        $response = $this->client->search([
            'index' => 'posts_index',
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'multi_match' => [
                                    'query' => implode(' ', $keywords),
                                    'fields' => ['title', 'content', 'lead'],
                                ]
                            ],
                        ],
                        'filter' => [
                            [
                                'range' => [
                                    'published_at' => [
                                        'gte' => $start->toIso8601String(),
                                        'lte' => $end->toIso8601String(),
                                    ],
                                ]
                            ],
                        ]
                    ],
                ],
                'aggs' => [
                    'posts_per_day' => [
                        'date_histogram' => [
                            'field' => 'published_at',
                            'calendar_interval' => 'day',
                            'format' => 'yyyy-MM-dd',
                            'min_doc_count' => 0,
                            'extended_bounds' => [
                                'min' => $start->toDateString(),
                                'max' => $end->toDateString(),
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return collect(
            $response['aggs']['posts_per_day']['buckets'] ?? $response['aggregations']['posts_per_day']['buckets']
        )->map(function ($bucket) {
            return [
                'date' => $bucket['key_as_string'],
                'count' => $bucket['doc_count'],
            ];
        });
    }
}
