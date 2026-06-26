<?php

namespace App\Http\Controllers\Admin;

use App\Models\Comment;
use App\Services\Admin\CommentAdminService;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    public function __construct(
        private readonly CommentService $comment_service,
        private readonly CommentAdminService $comment_admin_service,
    ) {}

    public function list(Request $request): JsonResponse
    {
        $per_page = $request->per_page ?? 20;
        $filter = $request->filter ?? 'all';
        $sort = $request->sort ?? 'newest';

        $paginated = $this->comment_admin_service->list($per_page, $filter, $sort);

        return $this->respondWithJson([
            'comments' => $paginated->map(fn($c) => $this->comment_admin_service->formatComment($c)),
            'pagination' => $this->paginationMeta($paginated),
        ]);
    }

    public function recent(): JsonResponse
    {
        $comments = $this->comment_admin_service->recent();

        return $this->respondWithJson([
            'comments' => $comments->map(fn($c) => $this->comment_admin_service->formatComment($c)),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $comment = $this->comment_admin_service->find($id);

        return $this->respondWithJson([
            'comment' => $this->comment_admin_service->formatComment($comment),
        ]);
    }

    public function reports(int $id): JsonResponse
    {
        return $this->respondWithJson([
            'reports' => $this->comment_admin_service->reports($id),
        ]);
    }

    public function edit(int $id, Request $request): JsonResponse
    {
        $comment = Comment::where('id', $id)->where('status', 1)->firstOrFail();

        $body = $this->comment_service->sanitizeBody($request->input('body'));
        $this->comment_service->updateBody($comment, $body);

        return $this->respondWithJson(['body' => $comment->body]);
    }

    public function delete(int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $deleted = $this->comment_admin_service->deleteComment($comment);

        return $this->respondWithJson(['deleted' => $deleted]);
    }

    public function restore(int $id): JsonResponse
    {
        $comment = Comment::withTrashed()->where('id', $id)->where('deleted_by', 1)->firstOrFail();
        $this->comment_service->restoreComment($comment, to_all: true);

        return $this->respondWithJson(['ok' => true]);
    }
}
