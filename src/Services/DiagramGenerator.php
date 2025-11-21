<?php

namespace MimicAk\LaravelBlueprintVisualizer\Services;

use MimicAk\LaravelBlueprintVisualizer\Contracts\DiagramGeneratorContract;

class DiagramGenerator implements DiagramGeneratorContract
{
    public function toMermaid(array $schema): string
    {
        $lines = ['erDiagram'];
        $aliases = [];

        // Create entity definitions
        foreach ($schema as $tableName => $table) {
            $alias = strtoupper($tableName);
            $aliases[$tableName] = $alias;

            $lines[] = "    {$alias} {";

            foreach ($table['columns'] as $column => $type) {
                $lines[] = "        {$type} {$column}";
            }

            $lines[] = "    }";
        }

        // Relations from model data
        foreach ($schema as $tableName => $table) {
            $fromAlias = $aliases[$tableName] ?? strtoupper($tableName);

            foreach ($table['relations'] as $relation) {
                $relatedModel = $relation['related'] ?? null;
                if (!$relatedModel) {
                    continue;
                }

                $relatedInstance = new $relatedModel;
                $toTable = $relatedInstance->getTable();
                $toAlias = $aliases[$toTable] ?? strtoupper($toTable);

                [$left, $right] = $this->relationToMermaidCardinality($relation['type'] ?? 'Relation');

                $label = $relation['name'] ?? '';
                $lines[] = "    {$fromAlias} {$left}--{$right} {$toAlias} : \"{$label}\"";
            }
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    public function toDot(array $schema): string
    {
        $lines = ['digraph G {'];

        foreach ($schema as $tableName => $table) {
            $label = $tableName . '\\n' . implode('\\n', array_keys($table['columns']));
            $lines[] = "    \"{$tableName}\" [shape=record,label=\"{$label}\"];";
        }

        foreach ($schema as $tableName => $table) {
            foreach ($table['relations'] as $relation) {
                $relatedModel = $relation['related'] ?? null;
                if (!$relatedModel) {
                    continue;
                }

                $relatedInstance = new $relatedModel;
                $toTable = $relatedInstance->getTable();
                $typeLabel = $relation['type'] ?? 'relation';

                $lines[] = "    \"{$tableName}\" -> \"{$toTable}\" [label=\"{$typeLabel}\"];";
            }
        }

        $lines[] = '}';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * Map Laravel relation types to Mermaid cardinalities.
     *
     * @return array{0:string,1:string}
     */
    protected function relationToMermaidCardinality(string $type): array
    {
        switch ($type) {
            case 'HasMany':
            case 'MorphMany':
                return ['||', '}o']; // one-to-many

            case 'BelongsTo':
            case 'MorphTo':
                return ['o{', '||']; // many-to-one

            case 'BelongsToMany':
            case 'MorphToMany':
                return ['}o', 'o{']; // many-to-many

            case 'HasOne':
            case 'MorphOne':
                return ['||', '||']; // one-to-one

            default:
                return ['o|', 'o|'];  // unknown/other
        }
    }
}
