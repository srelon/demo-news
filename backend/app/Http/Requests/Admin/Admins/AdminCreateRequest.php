<?php

namespace App\Http\Requests\Admin\Admins;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class AdminCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rule_id' => ['required', Rule::exists('admin_rules', 'id')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('admin_users', 'email')],
            'password' => ['required', 'min:6', Rules\Password::defaults()],
        ];
    }
}
