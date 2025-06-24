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

            $refreshSecret = config('app.refresh_secret');
            if (!$refreshSecret) {
                return response()->json(['error' => 'JWT configuration error'], 500);
            }

            $refreshTokenDecoded = JWT::decode($refreshToken, new Key($refreshSecret, 'HS384'));

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

            $jwtSecret = config('app.jwt_secret');
            if (!$jwtSecret) {
                return response()->json(['error' => 'JWT configuration error'], 500);
            }

            $jwt = JWT::encode($token, $jwtSecret, 'HS256');
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
