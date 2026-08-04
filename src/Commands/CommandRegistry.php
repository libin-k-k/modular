<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

final class CommandRegistry
{
    /**
     * @return list<class-string>
     */
    public static function all(): array
    {
        return [
            InstallModularCommand::class,
            MakeModuleCommand::class,
            MakeCrudCommand::class,
            MakeControllerCommand::class,
            MakeModelCommand::class,
            MakeRequestCommand::class,
            MakeServiceCommand::class,
            MakeRepositoryCommand::class,
            MakeResourceCommand::class,
            MakeEventCommand::class,
            MakeListenerCommand::class,
            MakeJobCommand::class,
            MakeNotificationCommand::class,
            MakePolicyCommand::class,
            MakeMiddlewareCommand::class,
            MakeDtoCommand::class,
            MakeActionCommand::class,
            MakeEnumCommand::class,
            MakeRuleCommand::class,
            MakeTestCommand::class,
            MakeMigrationCommand::class,
            MakeFactoryCommand::class,
            MakeSeederCommand::class,
            MakeRouteCommand::class,
            MakeConfigCommand::class,
            MakeLangCommand::class,
            MakeViewCommand::class,
            MakeTraitCommand::class,
            MakeHelperCommand::class,
            MakeConsoleCommand::class,
            ListModulesCommand::class,
            InfoModuleCommand::class,
            StatusModulesCommand::class,
            EnableModuleCommand::class,
            DisableModuleCommand::class,
            CacheModulesCommand::class,
            ClearModulesCacheCommand::class,
            RenameModuleCommand::class,
            DeleteModuleCommand::class,
            DoctorModulesCommand::class,
        ];
    }
}
