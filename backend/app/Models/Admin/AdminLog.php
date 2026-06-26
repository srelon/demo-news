<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminLog extends Model
{
    use SoftDeletes;

    protected $table = 'admin_logs';

    protected $fillable = [
        'admin_id',
        'location',
        'ip',
        'browser',
        'table',
        'action',
        'old',
    ];
}
