<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private $successStatus = 200;

    /**
     * Handles user logins
     */
    public function login(){
        if (Auth::attempt(['email' => \request('email'), 'password' => \request('password')])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('my-app')->accessToken;
            return \response()->json(['success' => $success], $this->successStatus);
        } else {
            return \response()->json(['error' => 'Unauthorized'], 401);
        }
    }
}
