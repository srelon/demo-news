<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminNotificationsController extends Controller
{
    public function __construct(private readonly AdminNotificationService $service) {}

    public function recent(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        return $this->respondWithJson($this->service->getRecent($admin));
    }

    public function unreadCount(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        return $this->respondWithJson(['count' => $this->service->getUnreadCount($admin)]);
    }

    public function all(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        $paginated = $this->service->getAllPaginated($admin);

        return $this->respondWithJson([
            'notifications' => collect($paginated->items())
                ->map(fn($n) => $this->service->format($n))
                ->values(),
            'pagination' => $this->paginationMeta($paginated),
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        $this->service->markOneRead($admin, $id);
        return $this->respondWithJson([]);
    }
}
