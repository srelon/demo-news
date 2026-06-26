<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\GlobalTrait;
use App\Traits\RespondTrait;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use RespondTrait, GlobalTrait;
    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(LoginRequest $request)
    {

        $request->authenticate();

        $user= User::getProfile(Auth::user()->public_id)
            ->createLogs($request);

        return $this->respondWithJson([
            "user"=> $user
        ]);
//        return redirect()->intended(RouteServiceProvider::HOME);
    }


    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request['name'],
            'username' => $this->generateUsername($request['name']),
            'email' => $request['email'],
            'public_id' => $this->createPublicId('users'),
            'password' => Hash::make($request['password']),
        ])
            ->createUserData();

        event(new Registered($user));
        Auth::login($user);

        return $this->respondWithJson([
            "user"=> User::getProfile(Auth::user()->public_id),
        ]);
    }

    private function generateUsername(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '_', $name));
        $base = preg_replace('/_+/', '_', trim($base, '_'));
        $base = substr($base, 0, 20);

        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $i;
            $i++;
        }

        return $username;
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return $this->respondWithJson([
            "message"=> 'Success'
        ]);
    }
}
