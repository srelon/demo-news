<?php

namespace App\Http\Requests\Admin\Rss;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RssSourceEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => [
                'required',
                'url',
                'max:255',
                Rule::unique('rss_sources', 'url')->ignore($this->route('id')),
            ],
            'subcategory_id' => ['required', 'integer', 'exists:subcategories,id'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
