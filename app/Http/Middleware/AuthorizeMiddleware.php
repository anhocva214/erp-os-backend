<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Models\Role;
use App\Models\RolePermission;
use Closure;
use Exception;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use PhpParser\Builder\Use_;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AuthorizeMiddleware
{
    public function handle(Request $request, Closure $next, $permissions)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token not provided',
            ], Response::HTTP_UNAUTHORIZED);
        }
        try {

            $secret = config('app.jwt_secret');
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $decoded_array = (array)$decoded;
            if($decoded_array['role'] === 'customer'){
                $customer = Customer::find($decoded_array['sub']);
                if($customer->isLogin == 'false'){
                    return response()->json([
                        'error' => 'Unauthorized',
                    ], Response::HTTP_UNAUTHORIZED);
                }
            }
            if($decoded_array['role'] === 'admin'){
                $user = User::find($decoded_array['sub']);
                if($user->isLogin == 'false'){
                    return response()->json([
                        'error' => 'Unauthorized',
                    ], Response::HTTP_UNAUTHORIZED);
                }
            }
            $roleId = $decoded_array['roleId'];

            // Optimized permission check with caching
            if (strlen($permissions)) {
                $hasPermission = $this->checkUserPermission($roleId, $permissions);
                if (!$hasPermission) {
                    return response()->json([
                        'error' => 'Unauthorized',
                    ], Response::HTTP_UNAUTHORIZED);
                }
            }
            $request->attributes->set('data', $decoded_array);
            return $next($request);

        } catch (BeforeValidException $e) {
            return response()->json([
                'error' => 'Invalid token',
            ], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Token expired',
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Optimized permission check with caching
     */
    private function checkUserPermission($roleId, $permission): bool
    {
        // Super admin bypass
        if ($roleId == 1) {
            return true; // Super admin has all permissions
        }

        // Cache key for role permissions
        $cacheKey = "middleware_role_permissions_{$roleId}";

        // Get permissions from cache or database
        $userPermissions = Cache::remember($cacheKey, 3600, function () use ($roleId) {
            return DB::table('rolePermission')
                ->join('permission', 'rolePermission.permissionId', '=', 'permission.id')
                ->where('rolePermission.roleId', $roleId)
                ->pluck('permission.name')
                ->toArray();
        });

        return in_array($permission, $userPermissions);
    }
}
