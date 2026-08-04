<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeActionCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:action {module : Module name} {name : Action class path/name}';
    protected $description = 'Generate an action class inside a module.';

    protected function artifactType(): string
    {
        return 'action';
    }
}
