<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full article resource for single article page.
 */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'image' => $this->image,
            'views' => $this->views,
            'source_name' => $this->source_name,
            'source_url' => $this->source_url,
            'published_at' => $this->published_at?->toDateTimeString(),
            'comments_count' => $this->whenCounted('comments'),
            'author' => $this->whenLoaded('author', fn() => [
                'name' => $this->author?->name,
                'image' => $this->author?->img,
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
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_keywords' => $this->seo_keywords,
        ];
    }
}
