<?php

namespace MimicAk\LaravelBlueprintVisualizer\Contracts;

interface ModelReflectorContract
{
    /**
     * Scan for models and return normalized metadata keyed by table name.
     *
     * @return array<string, array>
     */
    public function scan(): array;
}
