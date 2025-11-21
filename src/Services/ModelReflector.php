<?php

namespace MimicAk\LaravelBlueprintVisualizer\Services;

use DirectoryIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use MimicAk\LaravelBlueprintVisualizer\Contracts\ModelReflectorContract;
use ReflectionClass;
use ReflectionMethod;

class ModelReflector implements ModelReflectorContract
{
    protected array $config;

    public function __construct(array $config = null)
    {
        $this->config = $config ?? config('erd-visualizer', []);
    }

    public function scan(): array
    {
        $paths = $this->config['model_paths'] ?? ['app/Models'];

        $models = [];

        foreach ($paths as $path) {
            $models = array_merge($models, $this->scanPath(app_path(trim(str_replace('app/', '', $path), '/'))));
        }

        // Normalize: key by table name
        $result = [];

        foreach ($models as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            /** @var \Illuminate\Database\Eloquent\Model $instance */
            $instance = new $modelClass;

            if (!$instance instanceof Model) {
                continue;
            }

            $table = $instance->getTable();

            $relations = $this->extractRelations(new ReflectionClass($modelClass), $instance);

            $result[$table] = [
                'model' => $modelClass,
                'table' => $table,
                'fillable' => $instance->getFillable(),
                'guarded' => $instance->getGuarded(),
                'casts' => $instance->getCasts(),
                'hidden' => $instance->getHidden(),
                'primaryKey' => $instance->getKeyName(),
                'relations' => $relations,
                'meta' => [],
            ];
        }

        return $result;
    }

    /**
     * Recursively scan a directory and build fully qualified model class names.
     *
     * @return array<int, string>
     */
    protected function scanPath(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $models = [];

        $iterator = new DirectoryIterator($path);

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }

            if ($fileinfo->isDir()) {
                $models = array_merge($models, $this->scanPath($fileinfo->getPathname()));
                continue;
            }

            if ($fileinfo->getExtension() !== 'php') {
                continue;
            }

            // Convert file path to FQCN, assuming PSR-4 "App\" for app/Models
            $relative = str_replace(app_path() . DIRECTORY_SEPARATOR, '', $fileinfo->getPathname());
            $class = 'App\\' . str_replace(['/', '\\'], '\\', substr($relative, 0, -4));

            $models[] = $class;
        }

        return $models;
    }

    /**
     * Extract relations by reflecting methods that return Relation instances.
     *
     * @return array<int, array{
     *   name: string,
     *   type: string,
     *   related: string,
     *   foreign_key?: string|null,
     *   local_key?: string|null
     * }>
     */
    protected function extractRelations(ReflectionClass $ref, Model $instance): array
    {
        $relations = [];

        /** @var ReflectionMethod $method */
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip inherited base model methods, scopes, static methods
            if ($method->isStatic()) {
                continue;
            }
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }
            if ($method->getDeclaringClass()->getName() === Model::class) {
                continue;
            }

            $name = $method->getName();

            // Skip known non-relation patterns
            if (str_starts_with($name, 'get') || str_starts_with($name, 'set')) {
                continue;
            }

            // Try calling the method, catching exceptions (conditional relations etc.)
            try {
                $relation = $method->invoke($instance);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$relation instanceof Relation) {
                continue;
            }

            $related = get_class($relation->getRelated());
            $type = class_basename($relation);

            $data = [
                'name' => $name,
                'type' => $type,
                'related' => $related,
            ];

            // Try to extract foreign/local keys when available
            foreach (['getForeignKeyName', 'getQualifiedForeignKeyName', 'getForeignKey'] as $fkMethod) {
                if (method_exists($relation, $fkMethod)) {
                    $data['foreign_key'] = $relation->{$fkMethod}();
                    break;
                }
            }

            foreach (['getLocalKeyName', 'getQualifiedParentKeyName', 'getOwnerKeyName'] as $lkMethod) {
                if (method_exists($relation, $lkMethod)) {
                    $data['local_key'] = $relation->{$lkMethod}();
                    break;
                }
            }

            $relations[] = $data;
        }

        // TODO: Traits-defined relations are covered because ReflectionClass includes trait methods.
        return $relations;
    }
}
