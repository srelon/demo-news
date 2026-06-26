<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavItem extends Model
{
    protected $fillable = ['category_id', 'order'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tabs(): HasMany
    {
        return $this->hasMany(NavTab::class)->orderBy('order');
    }
}
