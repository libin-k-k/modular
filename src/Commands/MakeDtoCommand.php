<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeDtoCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:dto {module : Module name} {name : DTO class path/name}';
    protected $description = 'Generate a DTO class inside a module.';

    protected function artifactType(): string
    {
        return 'dto';
    }
}
