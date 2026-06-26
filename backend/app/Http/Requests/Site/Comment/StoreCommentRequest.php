<?php

namespace App\Http\Requests\Site\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'article_id' => ['required', 'exists:articles,id'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'replied_to_comment_id' => ['nullable', 'exists:comments,id'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
