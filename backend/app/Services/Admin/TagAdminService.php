<?php

namespace App\Services\Admin;

use App\Models\Tag;
use App\Services\CacheService;
use Illuminate\Support\Str;

class TagAdminService
{
    public function list(int $per_page, ?string $search)
    {
        $query = Tag::withCount('articles')->latest();

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        return $query->paginate($per_page);
    }

    public function find(int $id): Tag
    {
        return Tag::withCount('articles')->findOrFail($id);
    }

    public function create(array $data): Tag
    {
        return Tag::create([
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['name']),
        ]);
    }

    public function update(int $id, array $data): Tag
    {
        $tag = Tag::findOrFail($id);
        $tag->update([
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['name']),
        ]);

        CacheService::flushOnTagWrite();

        return $tag->fresh();
    }

    public function delete(int $id): void
    {
        Tag::findOrFail($id)->delete();
        CacheService::flushOnTagWrite();
    }
}
