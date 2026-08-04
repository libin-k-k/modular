<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeEnumCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:enum {module : Module name} {name : Enum class path/name}';
    protected $description = 'Generate an enum inside a module.';

    protected function artifactType(): string
    {
        return 'enum';
    }
}
