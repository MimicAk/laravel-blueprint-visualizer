<?php

namespace MimicAk\LaravelBlueprintVisualizer\Contracts;

interface SchemaMergerContract
{
    /**
     * @param array<string, array> $models
     * @param array<string, array> $tables
     *
     * @return array<string, array>
     */
    public function merge(array $models, array $tables): array;
}
