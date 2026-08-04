<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeRepositoryCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:repository {module : Module name} {name : Repository class path/name}';
    protected $description = 'Generate a repository and interface inside a module.';

    protected function artifactType(): string
    {
        return 'repository';
    }
}
