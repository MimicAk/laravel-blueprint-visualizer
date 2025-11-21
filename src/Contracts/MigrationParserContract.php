<?php

namespace MimicAk\LaravelBlueprintVisualizer\Contracts;

interface MigrationParserContract
{
    /**
     * Parse migrations and return normalized table structures keyed by table name.
     *
     * @return array<string, array>
     */
    public function parse(): array;
}
