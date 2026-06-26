<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\DebugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebugController extends Controller
{
    public function __construct(private readonly DebugService $debug_service) {}

    public function debug(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        return $this->respondWithJson(
            $this->debug_service->getLogs($request->current, $admin)
        );
    }

    public function unreadCount(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        return $this->respondWithJson(['count' => $this->debug_service->getUnreadCount($admin)]);
    }

    public function markSeen(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        $this->debug_service->markSeen($admin);
        return $this->respondWithJson([]);
    }
}
