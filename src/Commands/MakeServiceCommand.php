<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Libinkk\Modular\Commands\Concerns\HandlesDependencyInjection;

class MakeServiceCommand extends BaseMakeArtifactCommand
{
    use HandlesDependencyInjection;

    protected $signature = 'modular:service
        {target : Module name OR class path/name}
        {name? : Class path/name when module is first argument}
        {--module= : Module name when using name-first style}
        {--m= : Module name alias}
        {--inject : Wire matching dependencies into related classes (skip prompt)}
        {--no-inject : Skip dependency wiring (skip prompt)}';

    protected $description = 'Generate a service class inside a module.';

    protected function artifactType(): string
    {
        return 'service';
    }
}
