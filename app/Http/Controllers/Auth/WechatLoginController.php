<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WechatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * 微信扫码登录
 * - 真实模式：WECHAT_APPID 已配置，前端展示官方二维码，微信回调 /login/wechat/callback
 * - 演示模式：未配置时展示模拟扫码，便于本地联调绑定/登录链路
 */
class WechatLoginController extends Controller
{
    /**
     * 创建一次扫码会话
     */
    public function qr(): JsonResponse
    {
        $session = WechatService::createSession();

        return response()->json($session);
    }

    /**
     * 轮询扫码状态：pending / ready（openid 已就绪）
     */
    public function status(string $scene): JsonResponse
    {
        $state = WechatService::getState($scene);

        if (! $state) {
            return response()->json(['status' => 'expired']);
        }

        if ($state['status'] === 'ready' && $state['openid']) {
            $user = User::where('wechat_openid', $state['openid'])->first();

            if ($user) {
                // 已绑定：直接登录
                Auth::login($user, true);
                request()->session()->regenerate();
                WechatService::forget($scene);

                return response()->json(['status' => 'success', 'redirect' => route('dashboard')]);
            }

            // 未绑定：跳绑定页
            return response()->json(['status' => 'need_bind', 'scene' => $scene]);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * 演示模式：模拟微信扫码成功
     */
    public function mock(string $scene): JsonResponse
    {
        $state = WechatService::getState($scene);

        if (! $state) {
            return response()->json(['message' => '会话已过期，请重新扫码'], 404);
        }

        WechatService::updateState($scene, [
            'status' => 'ready',
            'openid' => WechatService::mockOpenid($scene),
        ]);

        return response()->json(['status' => 'ready']);
    }

    /**
     * 真实微信回调（开放平台扫码，携带 code + state）
     */
    public function callback(Request $request): RedirectResponse
    {
        $code = $request->input('code');
        $scene = $request->input('state');

        if (! $code || ! $scene || ! WechatService::getState($scene)) {
            return redirect()->route('login')->with('error', '微信登录会话无效，请重试');
        }

        try {
            $info = WechatService::exchangeCode($code);
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        WechatService::updateState($scene, [
            'status' => 'ready',
            'openid' => $info['openid'],
            'unionid' => $info['unionid'] ?? null,
        ]);

        return redirect()->route('login.wechat.bind', ['scene' => $scene]);
    }

    /**
     * 绑定页：首次微信登录选择绑定已有账号或注册新账号
     */
    public function bind(string $scene): View
    {
        $state = WechatService::getState($scene);

        abort_unless($state && $state['status'] === 'ready' && $state['openid'], 419);

        return view('auth.wechat-bind', ['scene' => $scene]);
    }

    /**
     * 提交绑定：绑定已有账号（邮箱+密码）或注册新账号
     */
    public function bindStore(Request $request, string $scene): RedirectResponse
    {
        $state = WechatService::getState($scene);
        abort_unless($state && $state['status'] === 'ready' && $state['openid'], 419);

        $openid = $state['openid'];
        $mode = $request->input('mode', 'register');

        if ($mode === 'bind') {
            $data = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $user = User::where('email', $data['email'])->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                return back()->withErrors(['email' => '邮箱或密码不正确']);
            }

            $user->update([
                'wechat_openid' => $openid,
                'wechat_unionid' => $state['unionid'] ?? null,
                'wechat_nickname' => $state['nickname'] ?? null,
            ]);

            Auth::login($user, true);
        } else {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:50'],
                'phone' => ['nullable', 'regex:/^1[3-9]\d{9}$/'],
            ]);

            // 手机号若已注册则绑定到该账号
            $user = $data['phone'] ? User::where('phone', $data['phone'])->first() : null;

            if ($user) {
                $user->update(['wechat_openid' => $openid, 'wechat_unionid' => $state['unionid'] ?? null]);
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'email' => 'wechat_'.substr(md5($openid), 0, 12).'@ticket.local',
                    'role' => 'customer',
                    'password' => Hash::make(\Illuminate\Support\Str::random(16)),
                    'wechat_openid' => $openid,
                    'wechat_unionid' => $state['unionid'] ?? null,
                ]);
            }

            Auth::login($user, true);
        }

        $request->session()->regenerate();
        WechatService::forget($scene);

        session()->flash('success', '微信登录成功，欢迎 '.$user->name);

        return redirect()->intended(route('dashboard'));
    }
}
