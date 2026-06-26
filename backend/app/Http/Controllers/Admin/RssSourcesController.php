<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Rss\RssSourceCreateRequest;
use App\Http\Requests\Admin\Rss\RssSourceEditRequest;
use App\Jobs\FetchRssFeeds;
use App\Services\Admin\ArticleAdminService;
use App\Services\Admin\RssSourceAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RssSourcesController extends Controller
{
    public function __construct(private readonly RssSourceAdminService $service) {}

    public function items(Request $request): JsonResponse
    {
        return $this->respondWithJson($this->service->items(
            $request->integer('source_id'),
            $request->integer('per_page', 10),
            $request->input('search'),
        ));
    }

    public function retry(Request $request, int $id): JsonResponse
    {
        $result = $this->service->retryItem($id, $request->boolean('force'));

        if (!$result['ok']) {
            return $this->respondWithError([
                'message' => $result['reason'],
            ], 422);
        }

        return $this->respondWithJson($result);
    }

    public function deleteItem(int $id): JsonResponse
    {
        $this->service->deleteItem($id);

        return $this->respondWithJson(['deleted' => true]);
    }

    public function deleteRejected(Request $request): JsonResponse
    {
        $count = $this->service->deleteAllRejected($request->integer('source_id'));

        return $this->respondWithJson(['deleted' => $count]);
    }

    public function refreshArticles(Request $request): JsonResponse
    {
        return $this->respondWithJson($this->service->refreshArticlesBatch(
            $request->integer('source_id'),
            $request->integer('last_id', 0),
            min($request->integer('limit', 5), 20),
        ));
    }

    public function retryAll(Request $request): JsonResponse
    {
        return $this->respondWithJson($this->service->retryItemsBatch(
            $request->integer('source_id'),
            $request->integer('last_id', 0),
            min($request->integer('limit', 5), 20),
        ));
    }

    public function fetch(): JsonResponse
    {
        (new FetchRssFeeds)->handle();
        return $this->respondWithJson($this->service->list());
    }

    public function list(): JsonResponse
    {
        return $this->respondWithJson($this->service->list());
    }

    public function options(ArticleAdminService $articles): JsonResponse
    {
        return $this->respondWithJson([
            'subcategories' => $articles->subcategoryOptions(),
            'categories' => $articles->categoryOptions(),
        ]);
    }

    public function create(RssSourceCreateRequest $request): JsonResponse
    {
        return $this->respondWithJson($this->service->create($request->validated()));
    }

    public function edit(RssSourceEditRequest $request, int $id): JsonResponse
    {
        return $this->respondWithJson($this->service->update($id, $request->validated()));
    }

    public function toggle(int $id): JsonResponse
    {
        return $this->respondWithJson($this->service->toggle($id));
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->respondWithJson(['deleted' => true]);
    }
}
