<?php

namespace App\Console\Commands;

use App\Services\Contracts\ElasticsearchIndexManagerInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SeedElasticsearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-elasticsearch
                            {--filename=seed-data.json : Path to the JSON file containing seed data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Elasticsearch with initial data';

    public function __construct(
        private ElasticsearchIndexManagerInterface $elasticsearchIndexManager
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->option('filename');

        if (!Storage::disk('local')->exists($filename)) {
            $this->output->error("File not found in the local storage: $filename");

            return self::FAILURE;
        }

        $data = null;

        try {
            $data = Storage::disk('local')->get($filename);
        } catch (\Exception $e) {
            $this->output->error("Failed to read file from local disk: " . $e->getMessage());

            return self::FAILURE;
        }

        $decoded = json_decode($data, true);

        if (is_null($decoded)) {
            $this->output->error("Failed to decode JSON data from file: $filename");

            return self::FAILURE;
        }

        $this->output->info("Creating index 'posts_index'...");

        $result = $this->elasticsearchIndexManager->ensureIndexExists('posts_index', [
            'properties' => [
                'title' => ['type' => 'text'],
                'lead' => ['type' => 'text'],
                'content' => ['type' => 'text'],
                'published_at' => ['type' => 'date'],
                'categories' => ['type' => 'keyword'],
                'tags' => ['type' => 'keyword'],
                'news_agency_name' => ['type' => 'keyword'],
            ],
        ]);

        if (!$result) {
            $this->output->error("Failed to create index 'posts_index'.");

            return self::FAILURE;
        }

        $this->output->info("Inserting data into 'posts_index'...");

        $bulkBody = [];

        foreach ($decoded as $post) {
            $bulkBody[] = ['index' => ['_index' => 'posts_index', '_id' => $post['id']]];
            $bulkBody[] = $post;
        }

        $result = $this->elasticsearchIndexManager->bulkIndex('posts_index', $bulkBody);

        if (!$result) {
            $this->output->error("Failed to insert data into 'posts_index'.");

            return self::FAILURE;
        }

        $this->output->success("Successfully seeded Elasticsearch with data from $filename");

        return self::SUCCESS;
    }
}
