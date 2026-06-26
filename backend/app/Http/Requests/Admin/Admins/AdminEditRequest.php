<?php

namespace App\Http\Requests\Admin\Admins;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class AdminEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rule_id' => ['required', 'exists:admin_rules,id'],
            'status' => ['required'],
            'password' => ['nullable', 'min:6', Rules\Password::defaults()],
        ];
    }
}
