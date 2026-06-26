<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAccessesEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'accesses' => ['required', 'array'],
            'accesses.*.id' => ['required', 'integer', Rule::exists('admin_accesses', 'id')],
            'accesses.*.key' => ['required', 'string', 'max:255'],
        ];
    }
}
