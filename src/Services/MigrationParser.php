<?php

namespace MimicAk\LaravelBlueprintVisualizer\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use MimicAk\LaravelBlueprintVisualizer\Contracts\MigrationParserContract;
use MimicAk\LaravelBlueprintVisualizer\Support\Schema as SchemaHelper;

class MigrationParser implements MigrationParserContract
{
    protected string $migrationsPath;

    public function __construct(?string $migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? database_path('migrations');
    }

    public function parse(): array
    {
        $tables = [];

        $originalResolver = Schema::getFacadeRoot();

        // Swap Schema builder with a collector
        Schema::swap(new class($tables) {
            public array $tables;

            public function __construct(array &$tables)
            {
                $this->tables = &$tables;
            }

            public function create($table, \Closure $callback)
            {
                $this->ensureTable($table);
                $blueprint = new Blueprint($table);
                $callback($blueprint);

                $this->captureBlueprint($blueprint, $table, 'create');
            }

            public function table($table, \Closure $callback)
            {
                $this->ensureTable($table);
                $blueprint = new Blueprint($table);
                $callback($blueprint);

                $this->captureBlueprint($blueprint, $table, 'table');
            }

            protected function ensureTable(string $table): void
            {
                if (!isset($this->tables[$table])) {
                    $this->tables[$table] = SchemaHelper::emptyTable($table);
                }
            }

            protected function captureBlueprint(Blueprint $blueprint, string $table, string $mode): void
            {
                // WARNING: This uses protected properties via reflection.
                // We keep it minimal; you can refine later.

                $ref = new \ReflectionClass($blueprint);

                if ($ref->hasProperty('columns')) {
                    $prop = $ref->getProperty('columns');
                    $prop->setAccessible(true);
                    $columns = $prop->getValue($blueprint);

                    foreach ($columns as $column) {
                        $name = $column->getAttributes()['name'] ?? $column->name ?? null;
                        if (!$name) {
                            continue;
                        }
                        $type = $column->type ?? 'unknown';

                        $this->tables[$table]['columns'][$name] = $type;
                    }
                }

                if ($ref->hasProperty('commands')) {
                    $prop = $ref->getProperty('commands');
                    $prop->setAccessible(true);
                    $commands = $prop->getValue($blueprint);

                    foreach ($commands as $command) {
                        $name = $command->name ?? null;
                        if ($name === 'foreign') {
                            $this->tables[$table]['foreign_keys'][] = [
                                'columns'      => $command->columns ?? [],
                                'references'   => $command->references ?? [],
                                'on'           => $command->on ?? null,
                                'onDelete'     => $command->onDelete ?? null,
                                'onUpdate'     => $command->onUpdate ?? null,
                                'name'         => $command->index ?? null,
                                'mode'         => $mode,
                            ];
                        }

                        if (in_array($name, ['index', 'unique', 'primary'], true)) {
                            $this->tables[$table]['indexes'][] = [
                                'type'    => $name,
                                'columns' => $command->columns ?? [],
                                'name'    => $command->index ?? null,
                                'mode'    => $mode,
                            ];
                        }
                    }
                }
            }
        });

        // Load migration classes and execute their 'up' methods
        foreach (File::files($this->migrationsPath) as $file) {
            $path = $file->getPathname();
            require_once $path;

            $class = $this->guessMigrationClassName($file->getFilename());

            if (!class_exists($class)) {
                continue;
            }

            $migration = new $class;

            if (!method_exists($migration, 'up')) {
                continue;
            }

            try {
                $migration->up();
            } catch (\Throwable $e) {
                // We do not want to die on one bad migration, just skip
                continue;
            }
        }

        // Restore original Schema facade root
        Schema::swap($originalResolver);

        return $tables;
    }

    protected function guessMigrationClassName(string $filename): string
    {
        // 2021_01_01_000000_create_users_table.php -> CreateUsersTable
        $base = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
        $base = str_replace('.php', '', $base);

        $segments = explode('_', $base);
        $segments = array_map('ucfirst', $segments);

        return implode('', $segments);
    }
}
