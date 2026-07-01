<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Page\PageRequest;
use App\Models\Page;
use App\Traits\RespondTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PagesController extends Controller
{
    use RespondTrait;

    public function list(): JsonResponse
    {
        $pages = Page::select(['id', 'slug', 'title', 'active', 'deletion_protected', 'updated_at'])->get();

        return $this->respondWithJson($pages);
    }

    public function info(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        return $this->respondWithJson($page->only(['id', 'slug', 'title', 'content', 'active', 'deletion_protected']));
    }

    public function create(PageRequest $request): JsonResponse
    {
        $page = Page::create([
            'title' => $request->input('title'),
            'slug' => Str::slug($request->input('title')),
            'content' => $request->input('content'),
            'active' => $request->input('active', true),
            'deletion_protected' => $request->input('deletion_protected', false),
        ]);

        if ($this->isDemoAdmin()) {
            $page->update(['demo_created' => true]);
        }

        return $this->respondWithJson($page->only(['id', 'slug', 'title', 'content', 'active', 'deletion_protected']));
    }

    public function edit(int $id, PageRequest $request): JsonResponse
    {
        $page = Page::findOrFail($id);

        if ($this->isDemoAdmin() && !$page->demo_snapshot) {
            $page->update([
                'demo_edited' => true,
                'demo_snapshot' => $page->only(['slug', 'title', 'content', 'active', 'deletion_protected']),
            ]);
        }

        $page->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'active' => $request->input('active', $page->active),
            'deletion_protected' => $request->input('deletion_protected', $page->deletion_protected),
        ]);

        return $this->respondWithJson($page->only(['id', 'slug', 'title', 'content', 'active', 'deletion_protected']));
    }

    public function delete(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        if ($page->deletion_protected) {
            return $this->respondWithError('This page is protected from deletion.', 422);
        }

        $page->delete();

        return $this->respondWithJson(['success' => true]);
    }

    private function isDemoAdmin(): bool
    {
        $email = config('demo.admin_email');
        return $email && Auth::guard('admin')->user()?->email === $email;
    }
}
