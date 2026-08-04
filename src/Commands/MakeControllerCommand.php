<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeControllerCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:controller {module : Module name} {name : Controller class path/name}';
    protected $description = 'Generate a controller inside a module.';

    protected function artifactType(): string
    {
        return 'controller';
    }
}
