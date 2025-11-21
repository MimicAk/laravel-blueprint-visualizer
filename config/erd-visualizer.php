<?php

return [

    'model_paths' => [
        'app/Models',
    ],

    'exclude_models' => [
        //
    ],

    'exclude_tables' => [
        'migrations',
        'failed_jobs',
    ],

    'renderer' => env('ERD_VISUALIZER_RENDERER', 'mermaid'), // 'graphviz' | 'mermaid'

    'cache' => env('ERD_VISUALIZER_CACHE', true),

    'cache_path' => storage_path('erd/schema.json'),

    'ui' => [
        'enabled' => env('ERD_VISUALIZER_UI_ENABLED', true),
        'route_prefix' => 'blueprint-visualizer',
        'middleware' => ['web'], // developer can add 'auth' etc.
    ],
];
