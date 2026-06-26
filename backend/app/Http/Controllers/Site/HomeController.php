<?php

namespace App\Http\Controllers\Site;

use App\Services\HomeService;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __construct(private readonly HomeService $homeService) {}

    public function index(): JsonResponse
    {
        return $this->respondWithJson($this->homeService->getData());
    }
}
