<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegisterActivate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use JD\Cloudder\Facades\Cloudder;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|min:3|max:55',
            'lastname' => 'required|string|max:55',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'activation_token' => str::random(60),
        ]);
        $user->save();

        $user->notify(new RegisterActivate($user));

        return response()->json([
            'message' => 'An email with the confirmation link is sent to your email to complete your activation process.',
        ], 201);

    }

    /**
     * Signup activate a new user account.
     *@param  int  $token
     * @return \Illuminate\Http\Response
     */

    public function registerActivate($token)
    {
        $user = User::where('activation_token', $token)->first();
        if (!$user) {
            return response()->json([
                'message' => 'This activation token is invalid.',
            ], 404);
        }
        $user->active = true;
        $user->activation_token = '';
        $user->save();

        return redirect()->away('http://www.beipro.net/login');
    }

    /**
     * Login a registered user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);

        $loginData = request(['email', 'password']);
        $loginData['active'] = 1;
        $loginData['deleted_at'] = null;

        if (Auth::attempt($loginData)) {
            $user = $request->user();
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
            if ($request->remember_me) {
                $token->expires_at = Carbon::now()->addWeeks(1);
            }

            $token->save();
            return response()->json([
                'email' => $user->email,
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
                'message' => 'You have logged in succesfully',
            ]);
        } else {
            if ($loginData['active'] == !1) {
                return response()->json([
                    'message' => "Unauthorized, Activate account from  your inbox before continuing.",
                ], 401);
            } else {
                return response()->json([
                    'message' => "Unauthorized.Check that the email and password are correct",
                ], 401);
            }

        }

    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update profile name the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_name(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:55',
            'lastname' => 'required|string|max:55',
        ]);

        $user = $request->user();

        if ($user) {
            $firstname = $request->firstname;
            $lastname = $request->lastname;

            $user->firstname = is_null($firstname) ? $user->firstname : $firstname;
            $user->lastname = is_null($lastname) ? $user->lastname : $lastname;

            $user->save();

            return response()->json([
                "message" => "records updated successfully",
            ], 200);
        } else {
            return response()->json([
                "message" => "user not found",
            ], 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_avatar(Request $request)
    {
        $this->validate($request, [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');

            $name = $request->file('avatar')->getClientOriginalName();

            $avatar = $request->file('avatar')->getRealPath();

            Cloudder::upload($avatar, null);

            list($width, $height) = getimagesize($avatar);

            $avatar_url = Cloudder::show(Cloudder::getPublicId(), ["width" => $width, "height" => $height]);

        } else {
            $avatar_url = 'noavatar.jpg';
        }

        //save images url
        $user->avatar_url = $avatar_url;
        $user->save();

        //save to uploads directory
        $image->move(public_path("avatars"), $name);

        return response()->json([
            'message' => "'Success','You have successfully upload avatar.",
        ], 200);

    }

}
