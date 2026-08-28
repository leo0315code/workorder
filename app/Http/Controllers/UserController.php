<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount(['tickets', 'assignedTickets']);

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate(['role' => ['required', 'in:customer,agent,admin']]);

        // 不允许降级自己（避免把自己踢出管理）
        if ($user->id === $request->user()->id && $request->input('role') !== 'admin') {
            return back()->with('error', '不能修改自己的角色');
        }

        $user->update(['role' => $request->input('role')]);

        return back()->with('success', '用户 '.$user->name.' 角色已更新为 '.$request->input('role'));
    }
}
