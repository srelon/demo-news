<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard_service) {}

    public function admin(): JsonResponse
    {
        return $this->respondWithJson([
            'user' => $this->dashboard_service->getCurrentAdmin(),
            'is_production' => app()->isProduction(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return $this->respondWithJson($this->dashboard_service->stats());
    }
}
