<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands\Concerns;

trait HandlesDependencyInjection
{
    /**
     * --inject / --no-inject skip the prompt. With neither flag, ask interactively.
     */
    protected function shouldInjectDependencies(): bool
    {
        if ($this->option('no-inject')) {
            return false;
        }

        if ($this->option('inject')) {
            return true;
        }

        return $this->confirm('Wire matching dependencies into related classes?', true);
    }
}
