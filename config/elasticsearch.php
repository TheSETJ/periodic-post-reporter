<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Elasticsearch Connection
     |--------------------------------------------------------------------------
     |
     | These values configure the Elasticsearch client used throughout the
     | application. The host and port default to a local instance for
     | development.
     |
     */

    'host' => env('ELASTICSEARCH_HOST', '127.0.0.1'),
    'port' => env('ELASTICSEARCH_PORT', '9200'),
    'api_key' => env('ELASTICSEARCH_API_KEY'),

];
