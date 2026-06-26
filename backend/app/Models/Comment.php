<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;
    /** Relations needed to build the site URL of a comment. */
    public const ARTICLE_RELATIONS = [
        'article:id,title,image,slug,subcategory_id',
        'article.subcategory:id,slug,category_id',
        'article.subcategory.category:id,slug',
    ];

    protected $fillable = ['article_id', 'user_id', 'parent_id', 'replied_to_comment_id', 'body', 'status', 'deleted_by'];

    /**
     * Public site URL with the comment anchor. Requires ARTICLE_RELATIONS
     * to be loaded; returns null when any slug in the chain is missing.
     */
    public function siteUrl(): ?string
    {
        $article = $this->article;

        if (!$article || !$article->slug || !$article->subcategory?->slug || !$article->subcategory?->category?->slug) {
            return null;
        }

        $site_url = config('app.site_url', env('SITE_URL', ''));

        // pin puts the root comment on the first page and lets the site
        // expand the replies thread when the anchor points to a reply
        $pin = $this->parent_id ?? $this->id;

        return "{$site_url}/{$article->subcategory->category->slug}/{$article->subcategory->slug}/{$article->slug}?pin={$pin}#comment-{$this->id}";
    }

    /**
     * Active comments + moderator-deleted (shown as placeholder on frontend).
     * Admin-deleted (status=2, deleted_by=null) are excluded.
     */
    public function scopeVisible($query)
    {
        return $query->where(function ($q) {
            $q->where('status', '!=', 2)
              ->orWhere('deleted_by', 1);
        });
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function repliedToComment(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'replied_to_comment_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }
}
