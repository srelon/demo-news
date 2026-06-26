<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for article cards/lists.
 */
class ArticleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'image' => $this->image,
            'views' => $this->views,
            'published_at' => $this->published_at?->toDateTimeString(),
            'author' => $this->whenLoaded('author', fn() => [
                'name' => $this->author?->name,
            ]),
            'subcategory' => $this->whenLoaded('subcategory', fn() => [
                'id' => $this->subcategory->id,
                'name' => $this->subcategory->name,
                'slug' => $this->subcategory->slug,
                'category' => $this->subcategory->relationLoaded('category') ? [
                    'id' => $this->subcategory->category->id,
                    'name' => $this->subcategory->category->name,
                    'slug' => $this->subcategory->category->slug,
                ] : null,
            ]),
        ];
    }
}
