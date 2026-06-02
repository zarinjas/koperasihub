<?php

use App\Http\Controllers\Api\MemberSearchController;
use App\Services\PostcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/members/search', MemberSearchController::class);

Route::get('/postcode/{postcode}', function (string $postcode, PostcodeService $service): JsonResponse {
    $result = $service->lookup($postcode);
    if (! $result) {
        return response()->json(['error' => 'Poskod tidak ditemui.'], 404);
    }
    return response()->json($result);
});