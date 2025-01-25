<?php

return [
    'types' => [
        'revenue' => [
            'template' => 'reports.revenue', // example blade template
            'query_chunk_size' => 500,
        ],
        // Additional types here
    ],

    'delivery' => [
        'local' => [
            'path' => storage_path('app/reports'),
        ],
    ],
];
