<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Page\PageRequest;
use App\Models\Page;
use App\Traits\RespondTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PagesController extends Controller
{
    use RespondTrait;

    public function list(): JsonResponse
    {
        $pages = Page::select(['id', 'slug', 'title', 'active', 'updated_at'])->get();

        return $this->respondWithJson($pages);
    }

    public function info(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        return $this->respondWithJson($page->only(['id', 'slug', 'title', 'content', 'active']));
    }

    public function create(PageRequest $request): JsonResponse
    {
        $page = Page::create([
            'title' => $request->input('title'),
            'slug' => Str::slug($request->input('title')),
            'content' => $request->input('content'),
            'active' => $request->input('active', true),
        ]);

        return $this->respondWithJson($page->only(['id', 'slug', 'title', 'content', 'active']));
    }

    public function edit(int $id, PageRequest $request): JsonResponse
    {
        $page = Page::findOrFail($id);
        $page->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'active' => $request->input('active', $page->active),
        ]);

        return $this->respondWithJson($page->only(['id', 'slug', 'title', 'content', 'active']));
    }
}
