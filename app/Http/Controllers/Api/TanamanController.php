<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TanamanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function show(int|string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'id' => $id], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'id' => $id], 501);
    }

    public function destroy(int|string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'id' => $id], 501);
    }
}
