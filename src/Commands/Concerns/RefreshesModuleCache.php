<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands\Concerns;

use Libinkk\Modular\Support\ModuleRepository;

trait RefreshesModuleCache
{
    protected function refreshModuleCache(ModuleRepository $repository): void
    {
        if ((bool) config('modular.auto_refresh_cache', true)) {
            $repository->cache();
            $this->line('Module cache refreshed.');
        }
    }
}
