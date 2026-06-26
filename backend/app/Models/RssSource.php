<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RssSource extends Model
{
    protected $fillable = [
        'name', 'url', 'active', 'subcategory_id',
        'last_fetched_at', 'last_status', 'last_error',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_fetched_at' => 'datetime',
    ];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RssItem::class);
    }
}
