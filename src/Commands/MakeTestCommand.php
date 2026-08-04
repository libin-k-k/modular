<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeTestCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:test {module : Module name} {name : Test class path/name}';
    protected $description = 'Generate a test class inside a module.';

    protected function artifactType(): string
    {
        return 'test';
    }
}
