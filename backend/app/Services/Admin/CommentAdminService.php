<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminModeratorAccount;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Services\CommentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CommentAdminService
{
    public function __construct(
        private readonly CommentService $comment_service,
    ) {}

    /**
     * Moderator-account comments are removed physically, everything else is
     * marked as deleted by admin (status 3) so the site keeps the placeholder.
     * Returns 'hard' or 'soft' so the frontend knows how to update the list.
     */
    public function deleteComment(Comment $comment): string
    {
        $is_moderator = AdminModeratorAccount::where('user_id', $comment->user_id)->exists();

        if ($is_moderator) {
            $this->comment_service->moderatorDelete($comment);
        } else {
            $this->comment_service->softDelete($comment, deleted_by: 1, to_all: true, status: 3);
        }

        return 'soft';
    }

    public function list(int $per_page, string $filter, string $sort): LengthAwarePaginator
    {
        $query = Comment::with($this->commentWith())
            ->withCount(['reports', ...$this->comment_service->likesCount()]);

        if ($filter === 'reported') {
            // updated_at so a repeated report (updateOrCreate) bubbles the comment up
            $query->whereHas('reports')->where('status', 1)->orderBy(
                CommentReport::selectRaw('MAX(updated_at)')
                    ->whereColumn('comment_id', 'comments.id'),
                'desc',
            );
        } elseif ($filter === 'deleted') {
            // withTrashed includes soft-deleted moderator records (deleted_at is set)
            $query->withTrashed()->whereIn('status', [2, 3])->orderByDesc('updated_at');
        } else {
            $query->where('status', 1);
            $this->comment_service->applySort($query, $sort);
        }

        return $query->paginate($per_page);
    }

    public function recent(): Collection
    {
        return Comment::with($this->commentWith())
            ->withCount(['reports', ...$this->comment_service->likesCount()])
            ->where('status', 1)
            ->latest()
            ->limit(10)
            ->get();
    }

    public function find(int $comment_id): Comment
    {
        return Comment::withTrashed()
            ->with($this->commentWith())
            ->withCount(['reports', ...$this->comment_service->likesCount()])
            ->findOrFail($comment_id);
    }

    public function reports(int $comment_id): array
    {
        return CommentReport::with('user:id,name,username')
            ->where('comment_id', $comment_id)
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'reason' => $r->reason,
                'user' => $r->user ? [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                    'username' => $r->user->username,
                ] : null,
                'created_at' => $r->created_at->toISOString(),
            ])
            ->all();
    }

    public function formatComment(Comment $c): array
    {
        $article = $c->article;

        return [
            'id' => $c->id,
            'parent_id' => $c->parent_id,
            'body' => $c->body,
            'status' => $c->status,
            'deleted_by' => $c->deleted_by,
            'reports_count' => $c->reports_count ?? 0,
            'likes' => $c->likes_count ?? 0,
            'dislikes' => $c->dislikes_count ?? 0,
            'user' => $c->user ? [
                'id' => $c->user->id,
                'name' => $c->user->name,
                'username' => $c->user->username,
                'img' => $c->user->img,
                'is_moderator' => in_array($c->user->id, AdminModeratorAccount::userIds()),
            ] : null,
            'article' => $article ? [
                'id' => $article->id,
                'title' => $article->title,
                'image' => $article->image,
                'url' => $c->siteUrl(),
            ] : null,
            'created_at' => $c->created_at->toISOString(),
        ];
    }

    private function commentWith(): array
    {
        return [
            'user:id,name,username,img',
            ...Comment::ARTICLE_RELATIONS,
        ];
    }
}
