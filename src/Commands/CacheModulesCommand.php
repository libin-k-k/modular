<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleRepository;

class CacheModulesCommand extends Command
{
    protected $signature = 'modular:cache';

    protected $description = 'Cache discovered modules to bootstrap cache file.';

    public function handle(ModuleRepository $repository): int
    {
        $repository->cache();
        $this->info('Module cache generated successfully.');

        return self::SUCCESS;
    }
}
