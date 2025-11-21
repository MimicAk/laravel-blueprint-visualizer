<?php

namespace MimicAk\LaravelBlueprintVisualizer;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MimicAk\LaravelBlueprintVisualizer\Skeleton\SkeletonClass
 */
class LaravelBlueprintVisualizerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BlueprintVisualizer::class;
    }
}
