<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\Subcategory;
use App\Services\CacheService;
use Illuminate\Support\Str;

class CategoryAdminService
{
    public function listAll(): array
    {
        return Category::withCount('subcategories')
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    public function reorderCategories(array $items): void
    {
        foreach ($items as $item) {
            Category::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        CacheService::flushOnCategoryWrite();
    }

    public function delete(int $id): void
    {
        $category = Category::with('subcategories')->findOrFail($id);

        foreach ($category->subcategories as $sub) {
            $sub->articles()->delete();
            $sub->delete();
        }

        $category->delete();

        CacheService::flushOnCategoryWrite();
    }

    public function list(int $per_page, ?string $search)
    {
        $query = Category::withCount('subcategories')->orderBy('order');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        return $query->paginate($per_page);
    }

    public function find(int $id): Category
    {
        return Category::withCount('subcategories')
            ->with(['subcategories' => fn($q) => $q->select('id', 'category_id', 'name', 'slug', 'order')->withCount('articles')])
            ->findOrFail($id);
    }

    public function create(array $data): Category
    {
        $category = Category::create([
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['name']),
            'color' => $data['color'] ?? '#333333',
            'order' => $data['order'] ?? 0,
        ]);

        CacheService::flushOnCategoryWrite();

        return $category;
    }

    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);
        $category->update([
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['name']),
            'color' => $data['color'] ?? $category->color,
            'order' => $data['order'] ?? $category->order,
        ]);

        CacheService::flushOnCategoryWrite();

        return $this->find($id);
    }

    public function createSubcategory(int $category_id, array $data): Subcategory
    {
        $sub = Subcategory::create([
            'category_id' => $category_id,
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['name']),
            'order' => $data['order'] ?? 0,
        ]);

        CacheService::flushOnCategoryWrite();

        return $sub;
    }

    public function updateSubcategory(int $id, array $data): Subcategory
    {
        $sub = Subcategory::findOrFail($id);
        $sub->update([
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['name']),
            'order' => $data['order'] ?? $sub->order,
        ]);

        CacheService::flushOnCategoryWrite();

        return $sub;
    }

    public function deleteSubcategory(int $id): void
    {
        $sub = Subcategory::findOrFail($id);
        $sub->articles()->delete();
        $sub->delete();

        CacheService::flushOnCategoryWrite();
    }

    public function reorderSubcategories(array $items): void
    {
        foreach ($items as $item) {
            Subcategory::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        CacheService::flushOnCategoryWrite();
    }
}
