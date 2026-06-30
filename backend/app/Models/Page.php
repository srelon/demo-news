<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;
    protected $fillable = ['slug', 'title', 'content', 'active', 'demo_edited', 'demo_created', 'demo_snapshot'];

    protected $casts = [
        'active' => 'boolean',
        'demo_edited' => 'boolean',
        'demo_created' => 'boolean',
        'demo_snapshot' => 'array',
    ];
}
