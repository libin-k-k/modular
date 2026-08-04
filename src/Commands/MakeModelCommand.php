<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeModelCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:model {module : Module name} {name : Model class path/name}';
    protected $description = 'Generate a model inside a module.';

    protected function artifactType(): string
    {
        return 'model';
    }
}
