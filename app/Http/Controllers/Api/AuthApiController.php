<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API 认证（Sanctum token）
 *
 * 登录：手机号 或 邮箱 + 密码 → 返回 access_token + 用户信息
 * App / 小程序 / 第三方拿 token 后，请求头带 Authorization: Bearer <token>
 */
class AuthApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account' => ['required', 'string', 'max:255'],      // 手机号或邮箱
            'password' => ['required', 'string', 'min:8'],
            'device' => ['nullable', 'string', 'max:50'],        // token 名称，如 "iOS-14"/"小程序"
        ]);

        $user = User::where('phone', $data['account'])
            ->orWhere('email', $data['account'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'account' => ['账号或密码错误'],
            ]);
        }

        // 每次登录签发新 token（旧 token 仍有效；登出可单独吊销）
        $token = $user->createToken($data['device'] ?? 'api');

        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // 吊销当前请求携带的 token
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '已登出']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    /**
     * 对外暴露的用户信息（隐藏敏感字段）
     */
    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ];
    }
}
