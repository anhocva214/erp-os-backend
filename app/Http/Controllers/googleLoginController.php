<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class googleLoginController extends Controller
{
    public function handleGoogleCallback(): JsonResponse
    {
        try {
            $data = Request::all();
            $token = $data['credential'];
            $privateKey = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/certs'));

            $decodedHeader = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $token)[0]));
            $kid = $decodedHeader->kid;
            $publicKey = openssl_pkey_get_public($privateKey->$kid);
            $decodedToken = JWT::decode($token, new Key($publicKey, 'RS256'));

            //if user already exists
            $customer = Customer::with("role")->where('googleId', $decodedToken->sub)->first();

            if ($customer) {
                $jwtSecret = config('app.jwt_secret');
                if (!$jwtSecret) {
                    return response()->json(['error' => 'JWT configuration error'], 500);
                }

                $accessToken = accessToken($customer->id, $customer->role->name, $customer->roleId);
                $jwt = JWT::encode($accessToken, $jwtSecret, 'HS256');
                Customer::where('id', $customer->id)->update(['isLogin' => 'true']);
                unset($customer->password);
                unset($customer->isLogin);
                $customer->token = $jwt;
                if($customer->googleId){
                    if(strpos($customer->profileImage, 'googleusercontent') !== false){
                        $customer->profileImage = $customer->profileImage;
                    }else{
                        $customer->profileImage = $customer->profileImage ? url('/') . '/customer-profileImage/' . $customer->profileImage : null;
                    }
                }
                $converted = arrayKeysToCamelCase($customer->toArray());
                return response()->json($converted, 200);
            }

            //if user does not exist
            $customer = new Customer();
            $customer->roleId = 3;
            $customer->profileImage = $decodedToken->picture;
            $customer->firstName = $decodedToken->given_name;
            $customer->lastName = $decodedToken->family_name;
            $customer->username = $decodedToken->given_name;
            $customer->email = $decodedToken->email;
            $customer->googleId = $decodedToken->sub;
            $customer->password = Hash::make($decodedToken->sub);
            $customer->isLogin = 'true';
            $customer->save();

            $jwtSecret = config('app.jwt_secret');
            if (!$jwtSecret) {
                return response()->json(['error' => 'JWT configuration error'], 500);
            }

            $accessToken = accessToken($customer->id, $customer->role->name, $customer->roleId);
            $jwt = JWT::encode($accessToken, $jwtSecret, 'HS256');
            unset($customer->password);
            $customer->token = $jwt;
            $converted = arrayKeysToCamelCase($customer->toArray());
            return response()->json($converted, 200);

        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

}

//accessToken
function accessToken($sub, $role, $roleId): array
{
    return array(
        'sub' => $sub,
        'role' => $role,
        'roleId' => $roleId,
        'exp' => time() + 86400
    );
}
