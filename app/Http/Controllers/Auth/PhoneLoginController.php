<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 手机号登录：短信验证码（未注册手机号自动创建客户账号）
 */
class PhoneLoginController extends Controller
{
    /**
     * 发送验证码
     */
    public function sendCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
        ]);

        $phone = $request->input('phone');
        $key = 'sms:'.$phone.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => '发送过于频繁，请稍后再试'], 429);
        }
        RateLimiter::hit($key, 60);

        $code = SmsService::sendCode($phone, $request->ip());

        // 演示模式：直接把验证码返回给前端，方便本地联调
        $payload = ['message' => '验证码已发送', 'expires_in' => 300];
        if (SmsService::driver() === 'demo') {
            $payload['debug_code'] = $code;
        }

        return response()->json($payload);
    }

    /**
     * 手机号 + 验证码登录
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $phone = $request->input('phone');

        if (! SmsService::verify($phone, $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => __('验证码错误或已过期'),
            ]);
        }

        // 已注册用户直接登录；未注册自动创建客户账号
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            $user = User::create([
                'name' => '用户'.substr($phone, -4),
                'phone' => $phone,
                'email' => 'phone_'.$phone.'@ticket.local',
                'role' => 'customer',
                'password' => Hash::make(SmsService::randomPassword()),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        session()->flash('success', '登录成功，欢迎 '.$user->name);

        return redirect()->intended(route('dashboard'));
    }
}
