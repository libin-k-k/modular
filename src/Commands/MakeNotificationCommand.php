<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

class MakeNotificationCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:notification {module : Module name} {name : Notification class path/name}';
    protected $description = 'Generate a notification class inside a module.';

    protected function artifactType(): string
    {
        return 'notification';
    }
}
