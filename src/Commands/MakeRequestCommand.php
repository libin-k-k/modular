<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeRequestCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:request {module : Module name} {name : Request class path/name}';
    protected $description = 'Generate a request class inside a module.';

    protected function artifactType(): string
    {
        return 'request';
    }
}
