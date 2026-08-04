<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeResourceCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:resource {module : Module name} {name : Resource class path/name}';
    protected $description = 'Generate a resource class inside a module.';

    protected function artifactType(): string
    {
        return 'resource';
    }
}
