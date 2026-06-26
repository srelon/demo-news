<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Traits\GlobalTrait;
use App\Traits\RespondTrait;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    use RespondTrait, GlobalTrait;
    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(ResetPasswordRequest $request)
    {
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::broker('users')->sendResetLink(
            $request->only('email')
        );

        if($status == Password::RESET_LINK_SENT) {
            return $this->respondWithJson(["success"=> $status]);
        } else {
            return $this->respondWithError(['email' => [__($status)]]);
//            return back()->withInput($request->only('email'))
//                ->withErrors(['email' => __($status)]);
        }
    }
}
