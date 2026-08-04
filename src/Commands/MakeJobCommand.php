<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeJobCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:job {module : Module name} {name : Job class path/name}';
    protected $description = 'Generate a job class inside a module.';

    protected function artifactType(): string
    {
        return 'job';
    }
}
