<?php

namespace Modules\User\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Entities\User;
use Modules\User\Notifications\SignupActive;

/**
 * Class AuthController
 * @package Modules\User\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param Request $request
     * @return string
     * @throws Exception
     */
    public function signup(Request $request) {
        $request->validate([
           'name' => 'required|string',
           'email' => 'required|string|email|unique:users',
           'password' => 'required|string|confirmed'
       ]);

       $user = new User([
           'name' => $request->name,
           'email' => $request->email,
           'password' => bcrypt($request->password),
           'activation_token' => \Str::random(32)
       ]);

       $user->save();

       $user->notify(new SignupActive($user));

       return \response()->json([
           'message' => trans('user::messages.success')
       ], 201);
   }

    /**
     * Login user and create token
     * @param Request $request
     * @return JsonResponse
     */
   public function login(Request $request){
       $rules = [
           'email' => 'required|email',
           'password' => 'required|string',
           'remember_me' => 'boolean'
       ];

       $request->validate($rules);

       $credentials = \request(['email', 'password']);
       $credentials['active'] = 1;
       $credentials['deleted_at'] = null;

       if (!\Auth::attempt($credentials)) {
           return \response()->json([
               'message' => 'Unauthorized',
               'errors' => 'These credentials do not match our records',
           ],400);
       } else {
           $user = $request->user();
           $tokenResult = $user->createToken('Personal Access Token');
           $token = $tokenResult->token;

           if ($request->remember_me) {
               $token->expires_at = Carbon::now()->addWeek(1);
           }

           $token->save();

           return \response()->json([
               'access_token' => $tokenResult->accessToken,
               'token_type' => 'Bearer',
               'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
           ]);
       }
   }

    /**
     * Logout user (revoke the token)
     *
     * @param Request $request
     * @return JsonResponse
     */
   public function logout(Request $request) {
       $request->user()->token()->revoke();

       return \response()->json([
           'message' => 'Successfully logged out'
       ]);
   }

    /**
     * Confirm Account
     * @param $token
     * @return JsonResponse
     */
    public function signupActivate($token) {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return \response()->json([
                'message' => 'This activation token is invalid.'
            ], 404);
        }

        $user->active = true;
        $user->activation_token = '';
        $user->save();

        return $user;
    }

    /**
     * Get the authenticated User
     * @return JsonResponse
     */
   public function user() {
       $msg = trans('user::messages.success');
       echo $msg;
       return \response()->json(\Auth::user());
   }
}
