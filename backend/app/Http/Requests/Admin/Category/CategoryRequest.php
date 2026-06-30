<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->slug)) {
            $this->merge(['slug' => Str::slug($this->name ?? '')]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', Rule::unique('categories', 'slug')->ignore($id)->whereNull('deleted_at')],
            'color' => ['nullable', 'string', 'max:20'],
            'order' => ['nullable', 'integer'],
        ];
    }
}
