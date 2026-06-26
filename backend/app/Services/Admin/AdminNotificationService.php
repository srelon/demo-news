<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminModeratorAccount;
use App\Models\Admin\AdminUsers;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminNotificationService
{
    public function __construct(private readonly NotificationService $notification_service) {}

    private function modUserIds(AdminUsers $admin): array
    {
        return AdminModeratorAccount::where('admin_user_id', $admin->id)->pluck('user_id')->toArray();
    }

    public function getRecent(AdminUsers $admin, int $limit = 15): array
    {
        $user_ids = $this->modUserIds($admin);
        if (empty($user_ids)) {
            return ['notifications' => [], 'unread_count' => 0];
        }

        $notifications = UserNotification::with([
                'article:id,slug,subcategory_id',
                'article.subcategory:id,slug,category_id',
                'article.subcategory.category:id,slug',
                'user:id,name,img',
            ])
            ->whereIn('user_id', $user_ids)
            ->latest()
            ->limit($limit)
            ->get();

        $unread_ids = $notifications->whereNull('read_at')->pluck('id')->toArray();
        if (!empty($unread_ids)) {
            $this->notification_service->markBulkRead($unread_ids);
        }

        return [
            'notifications' => $notifications->map(fn($n) => $this->format($n))->values(),
            'unread_count' => $this->getUnreadCount($admin),
        ];
    }

    public function getAllPaginated(AdminUsers $admin, int $per_page = 20): LengthAwarePaginator
    {
        $user_ids = $this->modUserIds($admin);

        $paginated = UserNotification::with([
                'article:id,slug,subcategory_id',
                'article.subcategory:id,slug,category_id',
                'article.subcategory.category:id,slug',
                'user:id,name,img',
            ])
            ->whereIn('user_id', $user_ids)
            ->latest()
            ->paginate($per_page);

        $ids = collect($paginated->items())->pluck('id')->toArray();
        if (!empty($ids)) {
            $this->notification_service->markBulkRead($ids);
        }

        return $paginated;
    }

    public function getUnreadCount(AdminUsers $admin): int
    {
        $user_ids = $this->modUserIds($admin);
        if (empty($user_ids)) return 0;
        return UserNotification::whereIn('user_id', $user_ids)->whereNull('read_at')->count();
    }

    public function markOneRead(AdminUsers $admin, int $notification_id): void
    {
        $user_ids = $this->modUserIds($admin);
        $exists = UserNotification::whereIn('user_id', $user_ids)
            ->where('id', $notification_id)
            ->exists();

        if ($exists) {
            $this->notification_service->markBulkRead([$notification_id]);
        }
    }

    public function format(UserNotification $n): array
    {
        return array_merge($this->notification_service->format($n), [
            'moderator_user' => $n->user ? [
                'id' => $n->user->id,
                'name' => $n->user->name,
                'img' => $n->user->img,
            ] : null,
        ]);
    }
}
