<?php

namespace MimicAk\LaravelBlueprintVisualizer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MimicAk\LaravelBlueprintVisualizer\Services\BlueprintVisualizer;

class DiagramController extends Controller
{
    public function __construct(protected BlueprintVisualizer $visualizer)
    {
    }

    public function index()
    {
        return view('erd-visualizer::index');
    }

    public function schema()
    {
        return response()->json($this->visualizer->generateSchema());
    }

    public function diagram(Request $request)
    {
        $format = $request->get('format', config('erd-visualizer.renderer', 'mermaid'));

        $content = $format === 'dot'
            ? $this->visualizer->generateDot()
            : $this->visualizer->generateMermaid();

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
