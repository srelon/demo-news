<?php

namespace App\Services;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Predis\Client as PredisClient;

class CommentService
{
    public function __construct(
        private readonly PredisClient $predis,
    ) {}

    public function getForArticle(int $article_id, ?int $user_id, string $sort = 'newest', ?int $pin_id = null): LengthAwarePaginator
    {
        $query = Comment::visible()
            ->with($this->commentWith($user_id))
            ->withCount([...$this->likesCount(), 'replies' => fn($q) => $q->where('status', '!=', 2)])
            ->where('article_id', $article_id)
            ->whereNull('parent_id');

        if ($pin_id) {
            $query->orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END", [$pin_id]);
        }

        $this->applySort($query, $sort);

        return $query->paginate(20);
    }

    /**
     * Rating-based sorts use the likes/dislikes difference, so dislikes act
     * as a tie-breaker when likes are equal. Requires likesCount() withCount.
     */
    public function applySort($query, string $sort): void
    {
        $query->when($sort === 'oldest', fn($q) => $q->oldest())
            ->when($sort === 'rating' || $sort === 'likes', fn($q) => $q->orderByRaw('(likes_count - dislikes_count) DESC'))
            ->when($sort === 'dislikes', fn($q) => $q->orderByRaw('(dislikes_count - likes_count) DESC'))
            ->when($sort !== 'oldest', fn($q) => $q->latest());
    }

    public function getReplies(int $comment_id, ?int $user_id): Collection
    {
        return Comment::visible()
            ->with($this->commentWith($user_id))
            ->withCount($this->likesCount())
            ->where('parent_id', $comment_id)
            ->oldest()
            ->get();
    }

    public function create(array $data): Comment
    {
        // Without an explicit status the model attribute stays null after create
        // (the DB default is not hydrated back), breaking status checks downstream.
        $data['status'] = $data['status'] ?? 1;

        return Comment::create($data);
    }

    public function toggleLike(int $comment_id, int $user_id, int $opp_type): array
    {
        $like = CommentLike::firstOrNew([
            'comment_id' => $comment_id,
            'user_id' => $user_id,
        ]);

        $like->opp_type = ($like->opp_type === $opp_type) ? 0 : $opp_type;
        $like->save();

        $comment = Comment::withCount($this->likesCount())->findOrFail($comment_id);

        return [
            'opp_type' => $like->opp_type,
            'likes' => $comment->likes_count,
            'dislikes' => $comment->dislikes_count,
        ];
    }

    public function sanitizeBody(string $body): string
    {
        return strip_tags($body, '<b><i><u><s><a><br>');
    }

    public function loadNewComment(Comment $comment): void
    {
        $comment->load([
            'user:id,public_id,name,username,img',
            'repliedToComment.user:id,name,username',
            ...Comment::ARTICLE_RELATIONS,
        ]);
        $comment->likes_count = 0;
        $comment->dislikes_count = 0;
        $comment->replies_count = 0;
        $comment->setRelation('likes', collect());
    }

    public function publishNew(Comment $comment, bool $to_all = true): void
    {
        $this->publish('comment.new', (new CommentResource($comment))->resolve(), $comment->article_id, $to_all);
    }

    public function updateBody(Comment $comment, string $body): void
    {
        $comment->update(['body' => $body]);

        $this->publish('comment.updated', [
            'comment_id' => $comment->id,
            'body' => $comment->body,
        ], $comment->article_id);
    }

    public function softDelete(Comment $comment, ?int $deleted_by = null, bool $to_all = false, int $status = 2): void
    {
        $comment->update(['status' => $status, 'deleted_by' => $deleted_by]);

        $this->publish('comment.deleted', [
            'comment_id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'deleted_by' => $deleted_by,
            'status' => $status,
        ], $comment->article_id, $to_all);
    }

    public function moderatorDelete(Comment $comment): void
    {
        $now = now();

        // Soft-delete replies (query builder won't include already-deleted records)
        Comment::where('parent_id', $comment->id)->update([
            'status' => 3,
            'deleted_by' => 1,
            'deleted_at' => $now,
        ]);

        $comment->update(['status' => 3, 'deleted_by' => 1]);
        $comment->delete();

        $this->publish('comment.deleted', [
            'comment_id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'deleted_by' => null,
        ], $comment->article_id, true);
    }

    public function restoreComment(Comment $comment, bool $to_all = false): void
    {
        if ($comment->trashed()) {
            Comment::withTrashed()->where('parent_id', $comment->id)->restore();
            Comment::withTrashed()->where('parent_id', $comment->id)->update(['status' => 1, 'deleted_by' => null]);
            $comment->restore();
        }

        $comment->update(['status' => 1, 'deleted_by' => null]);

        $this->publish('comment.restored', [
            'comment_id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'body' => $comment->body,
        ], $comment->article_id, $to_all);
    }

    public function publishLike(int $article_id, int $comment_id, int $from_user_id, int $opp_type, int $likes, int $dislikes): void
    {
        $this->publish('like.updated', [
            'comment_id' => $comment_id,
            'from_user_id' => $from_user_id,
            'opp_type' => $opp_type,
            'likes' => $likes,
            'dislikes' => $dislikes,
        ], $article_id);
    }

    /**
     * Publish a WS event to the article channel and optionally to the
     * admin-wide comments channel. Failures are swallowed — realtime
     * updates must never break the request itself.
     */
    private function publish(string $event, array $data, int $article_id, bool $to_all = false): void
    {
        try {
            $payload = json_encode([
                'event' => $event,
                'data' => $data,
            ]);
            $this->predis->publish('article.' . $article_id, $payload);
            if ($to_all) {
                $this->predis->publish('comments.all', $payload);
            }
        } catch (\Throwable) {}
    }

    private function commentWith(?int $user_id): array
    {
        return [
            'user:id,public_id,name,username,img',
            'repliedToComment.user:id,name,username',
            'likes' => fn($q) => $user_id
                ? $q->where('user_id', $user_id)->select(['comment_id', 'opp_type'])
                : $q->whereRaw('0=1'),
        ];
    }

    public function likesCount(): array
    {
        return [
            'likes as likes_count' => fn($q) => $q->where('opp_type', 2),
            'likes as dislikes_count' => fn($q) => $q->where('opp_type', 1),
        ];
    }
}
