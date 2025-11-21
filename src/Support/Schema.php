<?php

namespace MimicAk\LaravelBlueprintVisualizer\Support;

class Schema
{
    public static function emptyTable(string $table): array
    {
        return [
            'name' => $table,
            'columns' => [],
            'relations' => [],
            'foreign_keys' => [],
            'indexes' => [],
            'meta' => [],
        ];
    }
}
