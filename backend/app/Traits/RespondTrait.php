<?php
namespace App\Traits;

trait RespondTrait
{

    protected function paginationMeta($paginated): array
    {
        return [
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'total' => $paginated->total(),
            'prev_page_url' => $paginated->currentPage() > 1 ? 'prev' : null,
            'next_page_url' => $paginated->currentPage() < $paginated->lastPage() ? 'next' : null,
        ];
    }

    protected function respondWithJson($content, $status = 200, array $headers = [], $options = 0){
        $response = [
            'data' => $content,
            'status' => $status,
        ];

        return response()->json($response, $status, $headers, $options);
    }
    protected function respondWithError($message = 'An error occurred', $code = 400, $status = 400, $headers = [], $options = 0){
        $content = [
            'status' => $code,
            'errors' => $message,
        ];

        return response()->json($content, $code, $headers, $options);
    }
}
