<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255', 'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:255', 'timezone' => 'nullable|timezone',
            'locale' => 'nullable|string|in:en,es',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->fill(collect($data)->except('avatar')->toArray());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Rules\Password::min(10)->mixedCase()->numbers()],
        ]);
        $request->user()->password = Hash::make($request->input('password'));
        $request->user()->save();
        // Invalidate all other sessions on password change.
        \Illuminate\Support\Facades\Auth::logoutOtherDevices($request->input('current_password'));
        audit('profile.password_changed');
        $request->user()->notify(new \App\Notifications\SecurityAlertNotification('Your password was changed. All other sessions were signed out.'));

        return back()->with('success', 'Password changed. Other sessions were signed out.');
    }

    public function destroy(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $user = $request->user();
        auth()->logout();
        $user->delete();
        $request->session()->invalidate();

        return redirect('/');
    }
}
