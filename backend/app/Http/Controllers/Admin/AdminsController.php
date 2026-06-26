<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Admins\AdminAccessesCreateRequest;
use App\Http\Requests\Admin\Admins\AdminAccessesEditRequest;
use App\Http\Requests\Admin\Admins\AdminCreateRequest;
use App\Http\Requests\Admin\Admins\AdminEditRequest;
use App\Http\Requests\Admin\Admins\AdminRuleCreateRequest;
use App\Services\Admin\AdminRuleService;
use App\Services\Admin\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminsController extends Controller
{
    public function __construct(
        private readonly AdminService     $admin_service,
        private readonly AdminRuleService $rule_service,
    ) {}

    public function admins(Request $request): JsonResponse
    {
        $admins = $this->admin_service->list(
            $request->per_page ?? 30,
            $request->search ?? null,
        );

        return $this->respondWithJson($admins);
    }

    public function info(int $id): JsonResponse
    {
        $admin = $this->admin_service->find($id);

        if (!$admin) {
            return $this->respondWithError(['message' => __('User no found')], 404);
        }

        return $this->respondWithJson([
            'admin' => $admin,
            'rules' => $this->rule_service->rules(),
        ]);
    }

    public function rules(): JsonResponse
    {
        return $this->respondWithJson($this->rule_service->rules());
    }

    public function accessesList(): JsonResponse
    {
        return $this->respondWithJson($this->rule_service->accessesList());
    }

    public function rulesCreate(AdminRuleCreateRequest $request): JsonResponse
    {
        $data = $this->rule_service->createRule($request->name, $request->accesses ?? []);

        return $this->respondWithJson($data);
    }

    public function accesses(int $id): JsonResponse
    {
        $data = $this->rule_service->accesses($id);

        if (!$data) {
            return $this->respondWithError(['message' => __('Rule no found')], 404);
        }

        return $this->respondWithJson($data);
    }

    public function edit(int $id, AdminEditRequest $request): JsonResponse
    {
        $admin = $this->admin_service->edit($id, $request->validated(), $request->file('img'));

        if (!$admin) {
            return $this->respondWithError(['message' => __('User no found')], 404);
        }

        return $this->respondWithJson($admin);
    }

    public function create(AdminCreateRequest $request): JsonResponse
    {
        $admin = $this->admin_service->create($request->validated(), $request->file('img'));

        return $this->respondWithJson($admin);
    }

    public function accessesCreate(AdminAccessesCreateRequest $request): JsonResponse
    {
        $access = $this->rule_service->createAccess($request->key, $request->descriptions);

        return $this->respondWithJson($access);
    }

    public function accessesEdit(Request $request, int $id): JsonResponse
    {
        if (!$request->accesses) {
            return $this->respondWithError('Error edit');
        }

        $data = $this->rule_service->editAccesses($id, $request->name, $request->accesses);

        if (!$data) {
            return $this->respondWithError(['message' => __('Rule no found')], 404);
        }

        return $this->respondWithJson($data);
    }
}
