<?php

namespace App\Http\Controllers\Site;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(): JsonResponse
    {
        $user = Auth::guard('web')->user();
        $notifications = $this->notificationService->getForUser($user->id);
        // Count fetched AFTER marking read — reflects only unread outside the top-10
        $unread_count = $this->notificationService->getUnreadCount($user->id);

        return $this->respondWithJson([
            'notifications' => $notifications->map(fn($n) => $this->notificationService->format($n)),
            'unread_count' => $unread_count,
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();
        $paginated = $this->notificationService->getAllPaginated($user->id);

        return $this->respondWithJson([
            'notifications' => collect($paginated->items())->map(fn($n) => $this->notificationService->format($n)),
            'pagination' => $this->paginationMeta($paginated),
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $user = Auth::guard('web')->user();
        return $this->respondWithJson([
            'count' => $this->notificationService->getUnreadCount($user->id),
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        $user = Auth::guard('web')->user();
        $this->notificationService->markOneRead($user->id, $id);
        return $this->respondWithJson(['ok' => true]);
    }
}
