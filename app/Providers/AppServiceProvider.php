<?php

namespace App\Providers;

use App\Repositories\Contracts\PostSearchRepositoryInterface;
use App\Repositories\ElasticsearchPostRepository;
use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ElasticsearchClient::class, function (Application $app) {
            return ElasticsearchClientBuilder::create()
                ->setHosts([$app['config']->get('elasticsearch.host') . ':' . $app['config']->get('elasticsearch.port')])
                ->setApiKey($app['config']->get('elasticsearch.api_key'))
                ->build();
        });

        $this->app->bind(PostSearchRepositoryInterface::class, ElasticsearchPostRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
