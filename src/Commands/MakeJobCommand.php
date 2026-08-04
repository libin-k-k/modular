<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeJobCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:job {target : Module name OR class path/name} {name? : Class path/name when module is first argument} {--module= : Module name when using name-first style} {--m= : Module name alias}';
    protected $description = 'Generate a job class inside a module.';

    protected function artifactType(): string
    {
        return 'job';
    }
}