<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's notification preferences (email / sla / ticket channels).
     */
    public function updateNotificationPrefs(Request $request): RedirectResponse
    {
        $valid = implode(',', User::NOTIFY_CHANNELS);
        $data = $request->validate([
            'prefs' => ['nullable', 'array'],
            'prefs.*' => ['in:'.$valid],
        ]);

        $user = $request->user();
        $selected = $data['prefs'] ?? [];

        // 构建完整偏好：未勾选的频道记为 false
        $prefs = [];
        foreach (User::NOTIFY_CHANNELS as $channel) {
            $prefs[$channel] = in_array($channel, $selected, true);
        }

        $user->update(['notification_prefs' => $prefs]);

        return Redirect::route('profile.edit')->with('status', 'prefs-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
