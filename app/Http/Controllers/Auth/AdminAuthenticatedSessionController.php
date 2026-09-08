<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'account' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $account = trim($request->input('account', ''));
        $remember = $request->boolean('remember');

        // 支持 邮箱 或 用户名 登录：按 account 查找用户
        $user = User::where('email', $account)
            ->orWhere('username', $account)
            ->first();

        $authOk = $user && Hash::check($request->input('password'), $user->password);

        if (! $authOk) {
            RateLimiter::hit($this->throttleKey($request));
            AuditService::logLogin($request, 'admin', $account, false, 'bad_credentials');

            throw ValidationException::withMessages([
                'account' => __('登录信息不正确'),
            ]);
        }

        // 登录用户
        Auth::login($user, $remember);

        // 关键：客户角色不能进入管理端
        if (! $user->isAgent()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey($request));
            AuditService::logLogin($request, 'admin', $account, false, 'not_agent', (int) $user->id);

            throw ValidationException::withMessages([
                'account' => __('该账号不是客服/管理员，请使用用户端登录'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        AuditService::logLogin($request, 'admin', $account, true, null, (int) $user->id);

        session()->flash('success', '欢迎回来，'.$user->name);

        return redirect()->intended(route($this->homeRoute()));
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'account' => __('尝试次数过多，请 :seconds 秒后再试', ['seconds' => $seconds]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return 'admin-login:'.Str::lower($request->input('account', '')).'|'.$request->ip();
    }
}
