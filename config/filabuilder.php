<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend Route Prefix
    |--------------------------------------------------------------------------
    | Prefix for public page routes. Empty string means pages at /{slug}.
    | Example: 'pages' → /pages/{slug}
    */
    'route_prefix' => '',

    /*
    |--------------------------------------------------------------------------
    | Default Page Status
    |--------------------------------------------------------------------------
    | Default status when creating a new page.
    */
    'default_status' => 'draft',

    /*
    |--------------------------------------------------------------------------
    | Built-in Blocks
    |--------------------------------------------------------------------------
    | Load the default set of built-in blocks.
    */
    'blocks' => [
        'default_blocks' => true,
    ],
];
