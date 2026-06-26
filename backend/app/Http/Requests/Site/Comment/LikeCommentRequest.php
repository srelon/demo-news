<?php

namespace App\Http\Requests\Site\Comment;

use Illuminate\Foundation\Http\FormRequest;

class LikeCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opp_type' => ['required', 'in:1,2'],
        ];
    }
}
