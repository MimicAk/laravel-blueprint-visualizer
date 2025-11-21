<?php

namespace MimicAk\LaravelBlueprintVisualizer\Services;

use MimicAk\LaravelBlueprintVisualizer\Contracts\SchemaMergerContract;

class SchemaMerger implements SchemaMergerContract
{
    public function merge(array $models, array $tables): array
    {
        $schema = [];

        // Start with tables from migrations as ground truth for columns
        foreach ($tables as $tableName => $table) {
            $schema[$tableName] = $table;
        }

        // Merge model metadata + relations
        foreach ($models as $tableName => $model) {
            if (!isset($schema[$tableName])) {
                $schema[$tableName] = [
                    'name' => $tableName,
                    'columns' => [],
                    'relations' => [],
                    'foreign_keys' => [],
                    'indexes' => [],
                    'meta' => [],
                ];
                $schema[$tableName]['meta']['warnings'][] = "Table '{$tableName}' missing in migrations.";
            }

            $schema[$tableName]['meta']['model'] = $model['model'] ?? null;
            $schema[$tableName]['meta']['primaryKey'] = $model['primaryKey'] ?? 'id';

            // Do not override columns; migrations are source of truth.
            $schema[$tableName]['meta']['fillable'] = $model['fillable'] ?? [];
            $schema[$tableName]['meta']['guarded'] = $model['guarded'] ?? [];
            $schema[$tableName]['meta']['casts'] = $model['casts'] ?? [];
            $schema[$tableName]['meta']['hidden'] = $model['hidden'] ?? [];

            // Relations from models override inferred ones (if you add inference later)
            $schema[$tableName]['relations'] = $model['relations'] ?? [];
        }

        // Pivot detection
        foreach ($schema as $tableName => &$table) {
            $fks = $table['foreign_keys'] ?? [];

            if (count($fks) === 2 && count($table['columns']) <= 3) {
                $table['meta']['is_pivot'] = true;
            } else {
                $table['meta']['is_pivot'] = false;
            }
        }

        return $schema;
    }
}
