<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Tag\TagRequest;
use App\Services\Admin\TagAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagsController extends Controller
{
    public function __construct(private readonly TagAdminService $service) {}

    public function list(Request $request): JsonResponse
    {
        return $this->respondWithJson($this->service->list(
            $request->integer('per_page', 20),
            $request->input('search'),
        ));
    }

    public function info(int $id): JsonResponse
    {
        return $this->respondWithJson($this->service->find($id));
    }

    public function create(TagRequest $request): JsonResponse
    {
        return $this->respondWithJson($this->service->create($request->validated()));
    }

    public function edit(TagRequest $request, int $id): JsonResponse
    {
        return $this->respondWithJson($this->service->update($id, $request->validated()));
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->respondWithJson(['deleted' => true]);
    }
}
