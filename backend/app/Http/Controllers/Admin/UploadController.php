<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Upload\UploadImageRequest;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    public function __construct(private readonly ImageService $imageService) {}

    public function image(UploadImageRequest $request): JsonResponse
    {
        $path = $this->imageService->upload($request->file('image'), 'content');

        return $this->respondWithJson([
            'url' => config('app.url') . '/' . $path,
        ]);
    }
}
