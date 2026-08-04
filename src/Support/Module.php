<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

final class Module
{
    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $description,
        public readonly string $version,
        public readonly bool $enabled,
        public readonly array $dependencies
    ) {
    }

    /**
     * @param array{name?: mixed, description?: mixed, version?: mixed, enabled?: mixed, dependencies?: mixed} $data
     */
    public static function fromArray(array $data, string $path): self
    {
        $dependencies = [];
        if (isset($data['dependencies']) && is_array($data['dependencies'])) {
            $dependencies = array_values(array_filter($data['dependencies'], static fn ($value): bool => is_string($value) && $value !== ''));
        }

        return new self(
            (string) ($data['name'] ?? basename($path)),
            $path,
            (string) ($data['description'] ?? ''),
            (string) ($data['version'] ?? '1.0.0'),
            (bool) ($data['enabled'] ?? true),
            $dependencies
        );
    }

    /**
     * @return array{name: string, description: string, version: string, enabled: bool, dependencies: list<string>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'enabled' => $this->enabled,
            'dependencies' => $this->dependencies,
        ];
    }
}
