<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeRuleCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:rule {module : Module name} {name : Rule class path/name}';
    protected $description = 'Generate a rule class inside a module.';

    protected function artifactType(): string
    {
        return 'rule';
    }
}
