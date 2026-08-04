<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeServiceCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:service {module : Module name} {name : Service class path/name}';
    protected $description = 'Generate a service class inside a module.';

    protected function artifactType(): string
    {
        return 'service';
    }
}
