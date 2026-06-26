<?php

namespace App\Http\Controllers\Site;

use App\Http\Requests\Site\Comment\LikeCommentRequest;
use App\Http\Requests\Site\Comment\ReportCommentRequest;
use App\Http\Requests\Site\Comment\StoreCommentRequest;
use App\Http\Requests\Site\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Admin\AdminModeratorAccount;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Services\CommentService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $comment_service,
        private readonly NotificationService $notification_service,
    ) {}

    public function index(Request $request, int $article_id): JsonResponse
    {
        $user = Auth::guard('web')->user();
        $sort = $request->query('sort', 'newest');
        $pin_id = $request->query('pin') ? (int) $request->query('pin') : null;

        $paginated = $this->comment_service->getForArticle($article_id, $user?->id, $sort, $pin_id);

        return $this->respondWithJson([
            'comments' => CommentResource::collection($paginated->items()),
            'pagination' => $this->paginationMeta($paginated),
        ]);
    }

    public function replies(int $comment_id): JsonResponse
    {
        $user = Auth::guard('web')->user();
        $replies = $this->comment_service->getReplies($comment_id, $user?->id);

        return $this->respondWithJson([
            'replies' => CommentResource::collection($replies),
        ]);
    }

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $user = Auth::guard('web')->user();

        $body = $this->comment_service->sanitizeBody($request->input('body'));

        $comment = $this->comment_service->create([
            'article_id' => $request->input('article_id'),
            'user_id' => $user->id,
            'parent_id' => $request->input('parent_id'),
            'replied_to_comment_id' => $request->input('replied_to_comment_id'),
            'body' => $body,
        ]);

        $notify_comment_id = $request->input('replied_to_comment_id') ?? $request->input('parent_id');
        if ($notify_comment_id) {
            $notify_comment = Comment::find($notify_comment_id);
            if ($notify_comment && $notify_comment->user_id && $notify_comment->user_id !== $user->id) {
                $this->notification_service->create(
                    user_id: $notify_comment->user_id,
                    type: 'reply',
                    data: [
                        'from_name' => $user->name,
                        'from_username' => $user->username,
                        'comment_preview' => mb_substr(strip_tags($body), 0, 100),
                    ],
                    article_id: $request->input('article_id'),
                    comment_id: $comment->id,
                    parent_id: $request->input('parent_id'),
                );
            }
        }

        $this->comment_service->loadNewComment($comment);
        $this->comment_service->publishNew($comment);

        return $this->respondWithJson([
            'comment' => (new CommentResource($comment))->resolve(),
        ]);
    }

    public function like(LikeCommentRequest $request, int $comment_id): JsonResponse
    {

        $user = Auth::guard('web')->user();
        $result = $this->comment_service->toggleLike($comment_id, $user->id, (int) $request->input('opp_type'));

        $comment = Comment::find($comment_id);

        if ($comment && $comment->user_id && $comment->user_id !== $user->id) {
            if ($result['opp_type'] === 0) {
                $this->notification_service->deleteReaction($comment->user_id, $comment_id, $user->id);
            } else {
                $this->notification_service->upsertReaction(
                    user_id: $comment->user_id,
                    type: $result['opp_type'] === 2 ? 'like' : 'dislike',
                    data: [
                        'from_name' => $user->name,
                        'from_username' => $user->username,
                    ],
                    article_id: $comment->article_id,
                    comment_id: $comment_id,
                    parent_id: $comment->parent_id,
                    from_user_id: $user->id,
                );
            }
        }

        $this->comment_service->publishLike(
            $comment->article_id,
            $comment_id,
            $user->id,
            $result['opp_type'],
            $result['likes'],
            $result['dislikes'],
        );

        return $this->respondWithJson($result);
    }

    public function update(UpdateCommentRequest $request, int $comment_id): JsonResponse
    {

        $user = Auth::guard('web')->user();
        $comment = Comment::where('id', $comment_id)->where('user_id', $user->id)->firstOrFail();

        if ($comment->created_at->lt(now()->subDay())) {
            return $this->respondWithError('Comment can no longer be edited', 403);
        }

        $body = $this->comment_service->sanitizeBody($request->input('body'));
        $this->comment_service->updateBody($comment, $body);

        return $this->respondWithJson(['body' => $comment->body]);
    }

    public function destroy(int $comment_id): JsonResponse
    {
        $user = Auth::guard('web')->user();
        $comment = Comment::where('id', $comment_id)->where('user_id', $user->id)->firstOrFail();

        $this->comment_service->softDelete($comment, status: 2);

        return $this->respondWithJson(['ok' => true]);
    }

    public function moderatorDestroy(int $comment_id): JsonResponse
    {
        [$user, $error] = $this->requireModerator();
        if ($error) return $error;

        $comment = Comment::where('id', $comment_id)->where('status', '!=', 2)->firstOrFail();
        $is_moderator_comment = AdminModeratorAccount::where('user_id', $comment->user_id)->exists();

        if ($is_moderator_comment) {
            $this->comment_service->moderatorDelete($comment);
        } else {
            $this->comment_service->softDelete($comment, deleted_by: 1, status: 3);
        }

        return $this->respondWithJson(['ok' => true]);
    }

    public function moderatorUpdate(Request $request, int $comment_id): JsonResponse
    {
        [$user, $error] = $this->requireModerator();
        if ($error) return $error;

        $comment = Comment::where('id', $comment_id)
            ->where('user_id', $user->id)
            ->where('status', '!=', 2)
            ->firstOrFail();

        $body = $this->comment_service->sanitizeBody($request->input('body'));
        $this->comment_service->updateBody($comment, $body);

        return $this->respondWithJson(['body' => $comment->body]);
    }

    public function moderatorRestore(int $comment_id): JsonResponse
    {
        [, $error] = $this->requireModerator();
        if ($error) return $error;

        $comment = Comment::where('id', $comment_id)->where('deleted_by', 1)->firstOrFail();
        $this->comment_service->restoreComment($comment);

        return $this->respondWithJson(['ok' => true]);
    }

    public function report(ReportCommentRequest $request, int $comment_id): JsonResponse
    {
        $user = Auth::guard('web')->user();

        CommentReport::updateOrCreate(
            ['comment_id' => $comment_id, 'user_id' => $user->id],
            ['reason' => $request->input('reason')],
        );

        return $this->respondWithJson(['reported' => true]);
    }

    private function requireModerator(): array
    {
        $user = Auth::guard('web')->user();

        if (!$user) {
            return [null, $this->respondWithError('Unauthenticated', 401)];
        }

        if (!AdminModeratorAccount::where('user_id', $user->id)->exists()) {
            return [null, $this->respondWithError('Access denied', 403)];
        }

        return [$user, null];
    }
}
