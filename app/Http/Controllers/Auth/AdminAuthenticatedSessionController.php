<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * 管理端登录：仅允许 客服(agent)/管理员(admin) 角色
 */
class AdminAuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        // 已登录：管理端直接进后台
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.admin-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('登录信息不正确'),
            ]);
        }

        $user = Auth::user();

        // 关键：客户角色不能进入管理端
        if (! $user->isAgent()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('该账号不是客服/管理员，请使用用户端登录'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        session()->flash('success', '欢迎回来，'.$user->name);

        return redirect()->intended(route('dashboard'));
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('尝试次数过多，请 :seconds 秒后再试', ['seconds' => $seconds]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return 'admin-login:'.Str::lower($request->input('email', '')).'|'.$request->ip();
    }
}
