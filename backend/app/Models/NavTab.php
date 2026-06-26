<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavTab extends Model
{
    protected $fillable = ['nav_item_id', 'subcategory_id', 'order'];

    public function navItem(): BelongsTo
    {
        return $this->belongsTo(NavItem::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }
}
