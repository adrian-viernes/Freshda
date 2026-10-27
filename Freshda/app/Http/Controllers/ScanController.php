<?php

namespace App\Http\Controllers;

use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function __construct(private ScanService $scans) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        return response()->json(
            $this->scans->store($request->user(), $request->file('image')),
        );
    }
}
