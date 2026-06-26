<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserLog;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function createUserData(){
//        UsersData::create([
//            'user_id'=> $this->public_id,
//            'ref_link'=> $this->login.'#'.$this->public_id
//        ]);

        return $this;
    }


    static function getProfile($public_id): ?self
    {
        return User::select(['users.id', 'users.public_id', 'users.img', 'users.email', 'users.name', 'users.username'])
            ->where('public_id', $public_id)
            ->first();
    }


    public function createLogs($request): static
    {
        UserLog::create([
            'user_id' => $this->id,
            'ip' => $request->ip(),
            'browser' => $request->header('User-Agent'),
        ]);

        return $this;
    }
}
