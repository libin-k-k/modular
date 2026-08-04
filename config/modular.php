<?php

declare(strict_types=1);

return [
    'modules_path' => base_path('Modules'),
    'cache_file' => base_path('bootstrap/cache/modular_modules.php'),

    /*
    |--------------------------------------------------------------------------
    | Prefer module cache on boot
    |--------------------------------------------------------------------------
    |
    | When true and the cache file exists, enabled modules are resolved from
    | cache instead of scanning each module.json under Modules.
    |
    */
    'prefer_cache' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-refresh cache after lifecycle changes
    |--------------------------------------------------------------------------
    |
    | When true, make/enable/disable/rename/delete refresh the module cache.
    |
    */
    'auto_refresh_cache' => true,
];
