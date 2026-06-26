<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminAccesses extends Model
{
    use SoftDeletes;
    protected $table="admin_accesses";

    protected $fillable = [
        'key',
        'descriptions'
    ];

}
