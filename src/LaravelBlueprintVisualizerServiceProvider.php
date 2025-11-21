<?php

namespace MimicAk\LaravelBlueprintVisualizer;

use Illuminate\Support\ServiceProvider;
use MimicAk\LaravelBlueprintVisualizer\Console\GenerateDiagramCommand;
use MimicAk\LaravelBlueprintVisualizer\Contracts\DiagramGeneratorContract;
use MimicAk\LaravelBlueprintVisualizer\Contracts\MigrationParserContract;
use MimicAk\LaravelBlueprintVisualizer\Contracts\ModelReflectorContract;
use MimicAk\LaravelBlueprintVisualizer\Contracts\SchemaMergerContract;
use MimicAk\LaravelBlueprintVisualizer\Services\BlueprintVisualizer;
use MimicAk\LaravelBlueprintVisualizer\Services\DiagramGenerator;
use MimicAk\LaravelBlueprintVisualizer\Services\MigrationParser;
use MimicAk\LaravelBlueprintVisualizer\Services\ModelReflector;
use MimicAk\LaravelBlueprintVisualizer\Services\SchemaMerger;

class LaravelBlueprintVisualizerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-blueprint-visualizer');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-blueprint-visualizer');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/erd-visualizer.php' => config_path('erd-visualizer.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/erd-visualizer'),
            ], 'views');

            $this->commands([
                GenerateDiagramCommand::class,
                // ServeDiagramCommand::class,
            ]);
        }

        if (config('erd-visualizer.ui.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'erd-visualizer');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/erd-visualizer.php', 'erd-visualizer');

        $this->app->bind(ModelReflectorContract::class, ModelReflector::class);
        $this->app->bind(MigrationParserContract::class, MigrationParser::class);
        $this->app->bind(SchemaMergerContract::class, SchemaMerger::class);
        $this->app->bind(DiagramGeneratorContract::class, DiagramGenerator::class);

        $this->app->singleton(BlueprintVisualizer::class, function ($app) {
            return new BlueprintVisualizer(
                $app->make(ModelReflectorContract::class),
                $app->make(MigrationParserContract::class),
                $app->make(SchemaMergerContract::class),
                $app->make(DiagramGeneratorContract::class),
                $app['config']->get('erd-visualizer', [])
            );
        });
    }
}
