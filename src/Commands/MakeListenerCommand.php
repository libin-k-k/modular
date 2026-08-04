<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeListenerCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:listener {module : Module name} {name : Listener class path/name}';
    protected $description = 'Generate a listener class inside a module.';

    protected function artifactType(): string
    {
        return 'listener';
    }
}
