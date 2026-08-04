<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeEventCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:event {module : Module name} {name : Event class path/name}';
    protected $description = 'Generate an event class inside a module.';

    protected function artifactType(): string
    {
        return 'event';
    }
}
