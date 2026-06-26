<?php

namespace App\Http\Resources;

use App\Models\Admin\AdminModeratorAccount;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'body' => $this->body,
            'deleted_by' => $this->deleted_by,
            'status' => $this->status,
            'parent_id' => $this->parent_id,
            'reports_count' => $this->reports_count ?? 0,
            'likes' => $this->likes_count ?? 0,
            'dislikes' => $this->dislikes_count ?? 0,
            'replies_count' => $this->replies_count ?? 0,
            'user_reaction' => $this->likes->first()?->opp_type ?? 0,
            'article' => $this->when(
                $this->relationLoaded('article') && $this->article !== null,
                fn() => [
                    'id' => $this->article->id,
                    'title' => $this->article->title,
                    'image' => $this->article->image,
                    'url' => $this->resource->siteUrl(),
                ]
            ),
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'img' => $this->user->img,
                'is_moderator' => in_array($this->user->id, AdminModeratorAccount::userIds()),
            ] : null,
            'replied_to' => $this->repliedToComment ? [
                'id' => $this->repliedToComment->id,
                'user' => [
                    'name' => $this->repliedToComment->user?->name,
                    'username' => $this->repliedToComment->user?->username,
                ],
            ] : null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
