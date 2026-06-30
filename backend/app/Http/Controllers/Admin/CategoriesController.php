<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Category\CategoryRequest;
use App\Http\Requests\Admin\Category\SubcategoryRequest;
use App\Http\Requests\Admin\Category\SubcategoryReorderRequest;
use App\Services\Admin\CategoryAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function __construct(private readonly CategoryAdminService $service) {}

    public function all(): JsonResponse
    {
        return $this->respondWithJson($this->service->listAll());
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->service->reorderCategories($request->input('items', []));
        return $this->respondWithJson(['reordered' => true]);
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->respondWithJson(['deleted' => true]);
    }

    public function list(Request $request): JsonResponse
    {
        $paginator = $this->service->list(
            $request->integer('per_page', 20),
            $request->input('search'),
        );

        return $this->respondWithJson($paginator);
    }

    public function info(int $id): JsonResponse
    {
        return $this->respondWithJson($this->service->find($id));
    }

    public function create(CategoryRequest $request): JsonResponse
    {
        return $this->respondWithJson($this->service->create($request->validated()));
    }

    public function edit(CategoryRequest $request, int $id): JsonResponse
    {
        return $this->respondWithJson($this->service->update($id, $request->validated()));
    }

    public function subcategoryCreate(SubcategoryRequest $request, int $category_id): JsonResponse
    {
        return $this->respondWithJson($this->service->createSubcategory($category_id, $request->validated()));
    }

    public function subcategoryEdit(SubcategoryRequest $request, int $id): JsonResponse
    {
        return $this->respondWithJson($this->service->updateSubcategory($id, $request->validated()));
    }

    public function subcategoryDelete(int $id): JsonResponse
    {
        $this->service->deleteSubcategory($id);
        return $this->respondWithJson(['deleted' => true]);
    }

    public function subcategoryReorder(SubcategoryReorderRequest $request): JsonResponse
    {
        $this->service->reorderSubcategories($request->validated('items'));
        return $this->respondWithJson(['reordered' => true]);
    }
}
