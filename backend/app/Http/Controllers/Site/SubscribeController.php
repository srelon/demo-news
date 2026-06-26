<?php

namespace App\Http\Controllers\Site;

use App\Http\Requests\Site\SubscribeRequest;
use App\Services\SubscribeService;
use Illuminate\Http\JsonResponse;

class SubscribeController extends Controller
{
    public function __construct(private readonly SubscribeService $subscribeService) {}

    public function store(SubscribeRequest $request): JsonResponse
    {
        $this->subscribeService->subscribe($request->input('email'));

        return $this->respondWithJson(['message' => 'Subscribed successfully']);
    }
}
