<?php

namespace App\Http\Controllers;

use App\Services\BatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function __construct(private BatchService $batches) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            /* A batch needs the 3–5 captured fish photos the UI asks for. */
            'samples' => ['required', 'array', 'min:3', 'max:5'],
            'samples.*.name' => ['required', 'string', 'max:255'],
            'samples.*.cls' => ['required', 'in:fresh,moderate,notfresh'],
            'samples.*.label' => ['required', 'string', 'max:50'],
            'samples.*.conf' => ['required', 'numeric', 'min:0', 'max:100'],
            'verdict' => ['required', 'in:FRESH,MODERATE,NOT FRESH'],
            'confidence' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        return response()->json(
            $this->batches->store($request->user(), $validated),
        );
    }
}
