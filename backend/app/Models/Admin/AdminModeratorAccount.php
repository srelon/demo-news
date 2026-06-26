<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminModeratorAccount extends Model
{
    /** Per-request cache of linked moderator user ids. */
    private static ?array $cached_user_ids = null;

    protected $fillable = ['admin_user_id', 'user_id'];

    public static function userIds(): array
    {
        if (self::$cached_user_ids === null) {
            self::$cached_user_ids = self::pluck('user_id')->all();
        }

        return self::$cached_user_ids;
    }

    public static function resetUserIdsCache(): void
    {
        self::$cached_user_ids = null;
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUsers::class, 'admin_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
