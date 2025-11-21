<?php

namespace MimicAk\LaravelBlueprintVisualizer\Console;

use Illuminate\Console\Command;
use MimicAk\LaravelBlueprintVisualizer\Services\BlueprintVisualizer;

class GenerateDiagramCommand extends Command
{
    protected $signature = 'erd:generate {--format=mermaid} {--path=}';

    protected $description = 'Generate ERD schema and diagram files';

    public function handle(BlueprintVisualizer $visualizer): int
    {
        $format = $this->option('format');
        $path = $this->option('path') ?? storage_path('erd');

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $schema = $visualizer->generateSchema();
        file_put_contents($path . '/schema.json', json_encode($schema, JSON_PRETTY_PRINT));

        $this->info("Schema JSON written to {$path}/schema.json");

        if ($format === 'dot') {
            $content = $visualizer->generateDot();
            file_put_contents($path . '/schema.dot', $content);
            $this->info("DOT diagram written to {$path}/schema.dot");
        } else {
            $content = $visualizer->generateMermaid();
            file_put_contents($path . '/schema.mmd', $content);
            $this->info("Mermaid diagram written to {$path}/schema.mmd");
        }

        return 0;
    }
}
