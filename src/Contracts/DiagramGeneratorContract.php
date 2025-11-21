<?php

namespace MimicAk\LaravelBlueprintVisualizer\Contracts;

interface DiagramGeneratorContract
{
    /**
     * @param array<string, array> $schema
     */
    public function toMermaid(array $schema): string;

    /**
     * @param array<string, array> $schema
     */
    public function toDot(array $schema): string;
}
