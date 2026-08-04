<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakePolicyCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:policy {module : Module name} {name : Policy class path/name}';
    protected $description = 'Generate a policy class inside a module.';

    protected function artifactType(): string
    {
        return 'policy';
    }
}
