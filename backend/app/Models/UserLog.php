<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLog extends Model
{
    use SoftDeletes;

    protected $table = 'user_logs';

    protected $fillable = [
        'user_id',
        'location',
        'ip',
        'browser',
    ];
}
