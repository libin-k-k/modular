<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeMiddlewareCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:middleware {module : Module name} {name : Middleware class path/name}';
    protected $description = 'Generate a middleware class inside a module.';

    protected function artifactType(): string
    {
        return 'middleware';
    }
}
