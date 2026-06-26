<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedBlock extends Model
{
    protected $fillable = [
        'context', 'featured_id', 'top_right_id', 'bottom_left_id', 'bottom_right_id',
    ];

    public function featured(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'featured_id');
    }

    public function topRight(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'top_right_id');
    }

    public function bottomLeft(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'bottom_left_id');
    }

    public function bottomRight(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'bottom_right_id');
    }
}
