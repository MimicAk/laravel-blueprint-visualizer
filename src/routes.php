<?php

use Illuminate\Support\Facades\Route;
use MimicAk\LaravelBlueprintVisualizer\Http\Controllers\DiagramController;

Route::group([
    'prefix' => config('erd-visualizer.ui.route_prefix', 'blueprint-visualizer'),
    'middleware' => config('erd-visualizer.ui.middleware', ['web']),
], function () {
    Route::get('/', [DiagramController::class, 'index'])->name('erd-visualizer.index');
    Route::get('/schema', [DiagramController::class, 'schema'])->name('erd-visualizer.schema');
    Route::get('/diagram', [DiagramController::class, 'diagram'])->name('erd-visualizer.diagram');
});
