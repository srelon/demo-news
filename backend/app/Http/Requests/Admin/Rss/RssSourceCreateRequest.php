<?php

namespace App\Http\Requests\Admin\Rss;

use Illuminate\Foundation\Http\FormRequest;

class RssSourceCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255', 'unique:rss_sources,url'],
            'subcategory_id' => ['required', 'integer', 'exists:subcategories,id'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
