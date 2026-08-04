<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Commands\Concerns\SuggestsModules;
use Libinkk\Modular\Support\ModuleRepository;
use RuntimeException;

class InfoModuleCommand extends Command
{
    use SuggestsModules;

    protected $signature = 'modular:info {name : Module name}';

    protected $description = 'Show detailed information about a module.';

    public function handle(ModuleRepository $repository, Filesystem $files): int
    {
        $name = (string) $this->argument('name');

        try {
            $module = $this->requireModule($repository, $name);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $providerPath = $module->path . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $module->name . 'ServiceProvider.php';
        $counts = [
            'Controllers' => $this->countPhpFiles($files, $module->path . '/Controllers'),
            'Models' => $this->countPhpFiles($files, $module->path . '/Models'),
            'Services' => $this->countPhpFiles($files, $module->path . '/Services'),
            'Repositories' => $this->countPhpFiles($files, $module->path . '/Repositories'),
            'Routes' => $this->countPhpFiles($files, $module->path . '/Routes'),
            'Views' => $this->countFiles($files, $module->path . '/Views', ['blade.php']),
            'Translations' => $this->countPhpFiles($files, $module->path . '/Lang'),
            'Migrations' => $this->countPhpFiles($files, $module->path . '/Database/Migrations'),
            'Tests' => $this->countPhpFiles($files, $module->path . '/Tests'),
        ];

        $this->table(['Field', 'Value'], [
            ['Module Name', $module->name],
            ['Description', $module->description !== '' ? $module->description : '-'],
            ['Version', $module->version],
            ['Status', $module->enabled ? 'enabled' : 'disabled'],
            ['Provider', $files->exists($providerPath) ? $providerPath : 'missing'],
            ['Path', $module->path],
            ['Dependencies', $module->dependencies === [] ? '-' : implode(', ', $module->dependencies)],
            ['Controllers', (string) $counts['Controllers']],
            ['Models', (string) $counts['Models']],
            ['Services', (string) $counts['Services']],
            ['Repositories', (string) $counts['Repositories']],
            ['Routes', (string) $counts['Routes']],
            ['Views', (string) $counts['Views']],
            ['Translations', (string) $counts['Translations']],
            ['Migrations', (string) $counts['Migrations']],
            ['Tests', (string) $counts['Tests']],
        ]);

        return self::SUCCESS;
    }

    private function countPhpFiles(Filesystem $files, string $path): int
    {
        return $this->countFiles($files, $path, ['php']);
    }

    /**
     * @param list<string> $extensions
     */
    private function countFiles(Filesystem $files, string $path, array $extensions): int
    {
        if (!$files->isDirectory($path)) {
            return 0;
        }

        $count = 0;
        foreach ($files->allFiles($path) as $file) {
            $name = strtolower($file->getFilename());
            foreach ($extensions as $extension) {
                if (str_ends_with($name, '.' . strtolower($extension))) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }
}
