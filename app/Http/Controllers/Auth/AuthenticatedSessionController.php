<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $email = $request->input('email', '');

        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            // 记录失败登录（密码错误 / 限流）
            AuditService::logLogin($request, 'web', $email, false, 'bad_credentials');

            throw $e;
        }

        $request->session()->regenerate();

        // 记录成功登录
        AuditService::logLogin($request, 'web', $email, true, null, (int) Auth::id());

        return redirect()->intended(route($this->homeRoute(), absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
