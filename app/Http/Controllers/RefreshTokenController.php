<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Exception;
use Firebase\JWT\{JWT, Key};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Cookie;

class RefreshTokenController extends Controller
{
    public function validationRefreshToken(Request $request): JsonResponse
    {
        try {

        
            $refreshToken = $request->cookie('refreshToken');
            if (!$refreshToken) {
                return response()->json([
                    'error' => 'Forbidden',
                ], 403);
            }

            $secret = env('REFRESH_SECRET');
            $refreshTokenDecoded = JWT::decode($refreshToken, new Key($secret, 'HS384'));

            $user = Users::where('id', $refreshTokenDecoded->sub)->with('role:id,name')->first();
            if (!$user) {
                return response()->json([
                    'error' => 'Forbidden',
                ], 403);
            }

            if (time() > $refreshTokenDecoded->exp) {
                return response()->json([
                    'error' => 'Forbidden',
                ], 403);
            }

            $token = array(
                "sub" => $user['id'],
                "roleId" => $user['role']['id'],
                "role" => $user['role']['name'],
                "exp" => time() + 86400
            );

            $jwt = JWT::encode($token, env('JWT_SECRET'), 'HS256');
            $cookie = Cookie::make('refreshToken', $refreshToken);

            return response()->json([
                'token' => $jwt,
            ])->withCookie($cookie);

        } catch (Exception) {
            return response()->json(['error' => 'An error occurred during refreshing token. Please try again later.'
            ], 500);
        }
    }
}
