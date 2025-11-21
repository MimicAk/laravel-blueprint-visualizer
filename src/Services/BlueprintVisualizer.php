<?php

namespace MimicAk\LaravelBlueprintVisualizer\Services;

use MimicAk\LaravelBlueprintVisualizer\Contracts\DiagramGeneratorContract;
use MimicAk\LaravelBlueprintVisualizer\Contracts\MigrationParserContract;
use MimicAk\LaravelBlueprintVisualizer\Contracts\ModelReflectorContract;
use MimicAk\LaravelBlueprintVisualizer\Contracts\SchemaMergerContract;

class BlueprintVisualizer
{
    public function __construct(
        protected ModelReflectorContract $modelReflector,
        protected MigrationParserContract $migrationParser,
        protected SchemaMergerContract $schemaMerger,
        protected DiagramGeneratorContract $diagramGenerator,
        protected array $config = []
    ) {
    }

    /**
     * Generate normalized schema.
     *
     * @return array<string, array>
     */
    public function generateSchema(): array
    {
        $models = $this->modelReflector->scan();
        $tables = $this->migrationParser->parse();

        return $this->schemaMerger->merge($models, $tables);
    }

    public function generateMermaid(): string
    {
        return $this->diagramGenerator->toMermaid($this->generateSchema());
    }

    public function generateDot(): string
    {
        return $this->diagramGenerator->toDot($this->generateSchema());
    }
}
