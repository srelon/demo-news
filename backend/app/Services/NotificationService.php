<?php

namespace App\Services;

use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Predis\Client as PredisClient;

class NotificationService
{
    public function __construct(private readonly PredisClient $predis) {}

    public function create(
        int $user_id,
        string $type,
        array $data,
        ?int $article_id = null,
        ?int $comment_id = null,
        ?int $parent_id = null,
        ?int $from_user_id = null
    ): UserNotification {
        $notification = UserNotification::create([
            'user_id' => $user_id,
            'from_user_id' => $from_user_id,
            'type' => $type,
            'data' => $data,
            'article_id' => $article_id,
            'comment_id' => $comment_id,
            'parent_id' => $parent_id,
        ]);

        $this->publishUpserted($user_id, $notification);

        return $notification;
    }

    public function upsertReaction(
        int $user_id,
        string $type,
        array $data,
        ?int $article_id,
        int $comment_id,
        ?int $parent_id,
        int $from_user_id
    ): void {
        $existing = UserNotification::where([
            'user_id' => $user_id,
            'comment_id' => $comment_id,
            'from_user_id' => $from_user_id,
        ])->first();

        if ($existing) {
            $existing->update([
                'type' => $type,
                'data' => $data,
                'article_id' => $article_id,
                'parent_id' => $parent_id,
                // read_at is intentionally not reset
            ]);
            $notification = $existing->refresh();
        } else {
            $notification = UserNotification::create([
                'user_id' => $user_id,
                'from_user_id' => $from_user_id,
                'type' => $type,
                'data' => $data,
                'article_id' => $article_id,
                'comment_id' => $comment_id,
                'parent_id' => $parent_id,
            ]);
        }

        $this->publishUpserted($user_id, $notification);
    }

    public function deleteReaction(int $user_id, int $comment_id, int $from_user_id): void
    {
        $notification = UserNotification::where([
            'user_id' => $user_id,
            'comment_id' => $comment_id,
            'from_user_id' => $from_user_id,
        ])->first();

        if (!$notification) return;

        $id = $notification->id;
        $notification->delete();

        try {
            $this->predis->publish("notification.{$user_id}", json_encode([
                'action' => 'deleted',
                'id' => $id,
            ]));
        } catch (\Throwable) {}
    }

    public function getForUser(int $user_id, int $limit = 10): Collection
    {
        $notifications = UserNotification::with([
                'article:id,slug,subcategory_id',
                'article.subcategory:id,slug,category_id',
                'article.subcategory.category:id,slug',
            ])
            ->where('user_id', $user_id)
            ->latest()
            ->limit($limit)
            ->get();

        $this->markRead($user_id, $notifications->pluck('id')->toArray());

        return $notifications;
    }

    public function getAllPaginated(int $user_id, int $per_page = 20): LengthAwarePaginator
    {
        $paginated = UserNotification::with([
                'article:id,slug,subcategory_id',
                'article.subcategory:id,slug,category_id',
                'article.subcategory.category:id,slug',
            ])
            ->where('user_id', $user_id)
            ->latest()
            ->paginate($per_page);

        $this->markRead($user_id, collect($paginated->items())->pluck('id')->toArray());

        return $paginated;
    }

    public function getUnreadCount(int $user_id): int
    {
        return UserNotification::where('user_id', $user_id)->whereNull('read_at')->count();
    }

    public function markBulkRead(array $ids): void
    {
        if (empty($ids)) return;

        $user_ids = UserNotification::whereIn('id', $ids)
            ->whereNull('read_at')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        if (empty($user_ids)) return;

        UserNotification::whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        foreach ($user_ids as $user_id) {
            $this->publishUnreadCount($user_id);
        }
    }

    public function markOneRead(int $user_id, int $id): void
    {
        $affected = UserNotification::where('id', $id)
            ->where('user_id', $user_id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($affected > 0) {
            $this->publishUnreadCount($user_id);
        }
    }

    private function markRead(int $user_id, array $ids): void
    {
        if (empty($ids)) return;

        $affected = UserNotification::where('user_id', $user_id)
            ->whereNull('read_at')
            ->whereIn('id', $ids)
            ->update(['read_at' => now()]);

        if ($affected > 0) {
            $this->publishUnreadCount($user_id);
        }
    }

    private function publishUnreadCount(int $user_id): void
    {
        try {
            $this->predis->publish("notification.{$user_id}", json_encode([
                'action' => 'unread_count',
                'count' => $this->getUnreadCount($user_id),
            ]));
        } catch (\Throwable) {}
    }

    public function format(UserNotification $n): array
    {
        return [
            'id' => $n->id,
            'user_id' => $n->user_id,
            'type' => $n->type,
            'data' => $n->data,
            'article_id' => $n->article_id,
            'comment_id' => $n->comment_id,
            'parent_id' => $n->parent_id,
            'article_url' => $n->article ? [
                'slug' => $n->article->slug,
                'subcategory_slug' => $n->article->subcategory?->slug,
                'category_slug' => $n->article->subcategory?->category?->slug,
            ] : null,
            'read_at' => $n->read_at?->toISOString(),
            'created_at' => $n->created_at->toISOString(),
        ];
    }

    private function publishUpserted(int $user_id, UserNotification $notification): void
    {
        try {
            $this->predis->publish("notification.{$user_id}", json_encode([
                'action' => 'upserted',
                'notification' => $this->format($notification),
            ]));
        } catch (\Throwable) {}
    }
}
