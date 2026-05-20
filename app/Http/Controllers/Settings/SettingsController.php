<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class SettingsController extends Controller
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    public function profile(): View
    {
        return view('pages.settings.profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    public function security(DisableTwoFactorAuthentication $disableTwoFactor): View
    {
        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();
        $canManagePasskeys = Features::canManagePasskeys();

        if ($canManageTwoFactor
            && Fortify::confirmsTwoFactorAuthentication()
            && is_null(auth()->user()->two_factor_confirmed_at)) {
            $disableTwoFactor(auth()->user());
        }

        $twoFactorEnabled = $canManageTwoFactor
            ? auth()->user()->hasEnabledTwoFactorAuthentication()
            : false;
        $requiresConfirmation = $canManageTwoFactor
            ? Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')
            : false;

        return view('pages.settings.security', compact(
            'canManageTwoFactor',
            'canManagePasskeys',
            'twoFactorEnabled',
            'requiresConfirmation',
        ));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            return redirect()->route('security.edit')
                ->withErrors($e->errors())
                ->withInput();
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('security.edit')->with('status', 'password-updated');
    }

    public function appearance(): View
    {
        return view('pages.settings.appearance');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $user = Auth::user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
