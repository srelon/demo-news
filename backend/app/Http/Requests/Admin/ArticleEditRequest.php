<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', Rule::unique('articles', 'slug')->ignore($id)],
            'subcategory_id' => ['required', 'exists:subcategories,id'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'tags' => ['nullable', 'string'],
        ];

        $rules['seo_title']       = ['nullable', 'string', 'max:255'];
        $rules['seo_description'] = ['nullable', 'string', 'max:500'];
        $rules['seo_keywords']    = ['nullable', 'string', 'max:500'];

        if ($this->hasFile('image')) {
            $rules['image'] = ['image', 'max:5120'];
        }

        return $rules;
    }
}
