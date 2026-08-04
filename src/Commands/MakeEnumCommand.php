<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeEnumCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:enum {target : Module name OR class path/name} {name? : Class path/name when module is first argument} {--module= : Module name when using name-first style} {--m= : Module name alias}';
    protected $description = 'Generate an enum inside a module.';

    protected function artifactType(): string
    {
        return 'enum';
    }
}