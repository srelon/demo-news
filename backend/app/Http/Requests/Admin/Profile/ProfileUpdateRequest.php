<?php

namespace App\Http\Requests\Admin\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|min:4|max:255',
            'password' => 'nullable|string|min:6',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'allowed_ip' => 'nullable|ip',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->allowed_ip === '') {
            $this->merge(['allowed_ip' => null]);
        }
    }
}
