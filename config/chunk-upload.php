<?php

/**
 * @see https://github.com/pionl/laravel-chunk-upload
 */

return [
    /*
     * The storage config
     */
    'storage' => [
        /*
         * Returns the folder name of the chunks. The location is in storage/app/{folder_name}
         */
        'chunks' => 'chunks',
        'disk' => 'local',
    ],
    'clear' => [
        /*
         * How old chunks we should delete
         */
        'timestamp' => '-3 HOURS',
        'schedule' => [
            'enabled' => true,
            'cron' => '25 * * * *', // run every hour on the 25th minute
        ],
    ],
    'chunk' => [
        // setup for the chunk naming setup to ensure same name upload at same time
        'name' => [
            // API stateless (Bearer token via Sanctum, sem StartSession no grupo `api`) —
            // session()->getId() não persiste entre requests aqui, então usar sessão
            // quebraria o reagrupamento dos chunks. IP + navegador é estável nesse caso.
            'use' => [
                'session' => false,
                'browser' => true,
            ],
        ],
    ],
    'logging' => [
        // Enables the debug/info logging of parallel upload chunk and merge events.
        // Disabled by default as it can be noisy in production.
        'enabled' => env('CHUNK_UPLOAD_LOGGING_ENABLED', false),
    ],
    'handlers' => [
        // A list of handlers/providers that will be appended to existing list of handlers
        'custom' => [],
        // Overrides the list of handlers - use only what you really want
        'override' => [
            // \Pion\Laravel\ChunkUpload\Handler\DropZoneUploadHandler::class
        ],
    ],
];
