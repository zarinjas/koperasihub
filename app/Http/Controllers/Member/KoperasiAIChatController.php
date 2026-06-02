<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\AI\KoperasiAIChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KoperasiAIChatController extends Controller
{
    public function __construct(
        private readonly KoperasiAIChatService $chatService,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $reply = $this->chatService->ask($request->input('message'));

        return response()->json(['reply' => $reply]);
    }
}
