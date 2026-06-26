<?php

namespace App\Http\Controllers\Site;

use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        return $this->respondWithJson($page->only('id', 'slug', 'title', 'content'));
    }
}
