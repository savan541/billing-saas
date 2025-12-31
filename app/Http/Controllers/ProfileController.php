<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
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

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's email notification settings.
     */
    public function updateEmailSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_notifications_enabled' => 'required|boolean',
            'email_notification_preferences' => 'required|array',
            'email_notification_preferences.invoice_created' => 'required|boolean',
            'email_notification_preferences.invoice_paid' => 'required|boolean',
            'email_notification_preferences.payment_receipt' => 'required|boolean',
            'email_notification_preferences.recurring_invoice_generated' => 'required|boolean',
        ]);

        $user = $request->user();
        $user->email_notifications_enabled = $validated['email_notifications_enabled'];
        $user->email_notification_preferences = $validated['email_notification_preferences'];
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'email-settings-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
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
