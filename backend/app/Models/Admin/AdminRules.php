<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminRules extends Model
{
    use SoftDeletes;
    protected $table = 'admin_rules';

    protected $fillable = ['name', 'accesses_id', 'active'];

    protected $casts = [
        'accesses_id' => 'array',
    ];

}
