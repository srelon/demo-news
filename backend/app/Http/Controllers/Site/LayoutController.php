<?php

namespace App\Http\Controllers\Site;

use App\Services\LayoutService;
use Illuminate\Http\JsonResponse;

class LayoutController extends Controller
{
    public function __construct(private readonly LayoutService $layoutService) {}

    public function index(): JsonResponse
    {
        return $this->respondWithJson($this->layoutService->getData());
    }
}
