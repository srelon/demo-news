<?php

namespace App\Http\Requests\Admin\Session;

use Illuminate\Foundation\Http\FormRequest;

class SetModeratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
        ];
    }
}
