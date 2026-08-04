<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleRepository;

class ClearModulesCacheCommand extends Command
{
    protected $signature = 'modular:clear';

    protected $description = 'Clear module discovery cache file.';

    public function handle(ModuleRepository $repository): int
    {
        $deleted = $repository->clearCache();

        if ($deleted) {
            $this->info('Module cache cleared successfully.');
        } else {
            $this->warn('Module cache file does not exist.');
        }

        return self::SUCCESS;
    }
}
