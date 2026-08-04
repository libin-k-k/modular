<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeActionCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:action {target : Module name OR class path/name} {name? : Class path/name when module is first argument} {--module= : Module name when using name-first style} {--m= : Module name alias}';
    protected $description = 'Generate an action class inside a module.';

    protected function artifactType(): string
    {
        return 'action';
    }
}