<?php

namespace App\Models\Admin;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Admin\AdminLog;

class AdminUsers extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rule_id',
        'name',
        'img',
        'email',
        'password',
        'debug_last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $casts = [
        'accesses_id' => 'array',
        'debug_last_seen_at' => 'datetime',
    ];


    static function getProfile($public_id) {
        return AdminUsers::select(['admin_users.id', 'admin_users.rule_id', 'admin_rules.accesses_id', 'admin_users.img', 'admin_users.email', 'admin_users.status',  'admin_users.name'])
            ->where('admin_users.id', $public_id)
            ->join('admin_rules', 'admin_rules.id','=','admin_users.rule_id')
//            ->join('collections', 'collections.id','=','users.avatar_id')
            ->first();
    }


    public function createLogs($request): static
    {
        AdminLog::create([
            'admin_id' => $this->id,
            'ip' => $request->ip(),
            'browser' => $request->header('User-Agent'),
        ]);

        return $this;
    }
    public function hasAccess($module, $action)
    {
        $accesses = $this->role->accesses_id ?? [];

        return $accesses[$module][$action] ?? false;
    }


    public function rule()
    {
        return $this->hasOne('App\Models\Admin\AdminRules','id','rule_id')
            ->select(['id','name', 'accesses_id']);
    }

    public function moderatorAccounts()
    {
        return $this->belongsToMany(\App\Models\User::class, AdminModeratorAccount::class, 'admin_user_id', 'user_id');
    }

}
