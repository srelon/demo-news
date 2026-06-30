<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Article\ArticleRequest;
use App\Models\Tag;
use App\Services\Admin\ArticleAdminService;
use App\Services\Rss\RssFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticlesController extends Controller
{
    public function __construct(private readonly ArticleAdminService $service) {}

    public function list(Request $request): JsonResponse
    {
        $paginator = $this->service->list(
            $request->integer('per_page', 20),
            $request->input('search'),
            [
                'source_name' => $request->input('source_name'),
                'rss_source_id' => $request->input('rss_source_id'),
                'category_id' => $request->input('category_id'),
                'subcategory_id' => $request->input('subcategory_id'),
                'sort' => $request->input('sort'),
            ],
        );

        return $this->respondWithJson($paginator);
    }

    public function sourceOptions(): JsonResponse
    {
        return $this->respondWithJson($this->service->sourceNameOptions());
    }

    public function duplicates(): JsonResponse
    {
        return $this->respondWithJson($this->service->duplicateGroups());
    }

    public function cleanDuplicates(): JsonResponse
    {
        return $this->respondWithJson([
            'deleted' => $this->service->cleanDuplicates(),
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->respondWithJson(['deleted' => true]);
    }

    public function refresh(int $id, RssFeedService $rss): JsonResponse
    {
        $article = $this->service->find($id);

        if (!$rss->refreshArticle($article)) {
            return $this->respondWithError([
                'message' => 'Article has no RSS source or the source page is unreachable',
            ], 422);
        }

        return $this->respondWithJson($this->service->find($id));
    }

    public function info(int $id): JsonResponse
    {
        $article = $this->service->find($id);

        return $this->respondWithJson([
            'article' => $article,
            'tags' => $article->tags->pluck('name')->implode(', '),
            'tag_options' => Tag::select('id', 'name')->orderBy('name')->get(),
            'category_options' => $this->service->categoryOptions(),
            'subcategory_options' => $this->service->subcategoryOptions(),
        ]);
    }

    public function create(ArticleRequest $request): JsonResponse
    {
        $article = $this->service->create($request->validated(), $request->file('image'));

        return $this->respondWithJson($article);
    }

    public function edit(ArticleRequest $request, int $id): JsonResponse
    {
        $article = $this->service->update($id, $request->validated(), $request->file('image'));

        return $this->respondWithJson([
            'article' => $article,
            'tags' => $article->tags->pluck('name')->implode(', '),
            'tag_options' => Tag::select('id', 'name')->orderBy('name')->get(),
            'category_options' => $this->service->categoryOptions(),
            'subcategory_options' => $this->service->subcategoryOptions(),
        ]);
    }

    public function formOptions(): JsonResponse
    {
        return $this->respondWithJson([
            'tag_options' => Tag::select('id', 'name')->orderBy('name')->get(),
            'category_options' => $this->service->categoryOptions(),
            'subcategory_options' => $this->service->subcategoryOptions(),
        ]);
    }

    public function subcategoryOptions(): JsonResponse
    {
        return $this->respondWithJson($this->service->subcategoryOptions());
    }
}
