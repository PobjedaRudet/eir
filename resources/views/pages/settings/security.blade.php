<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Security settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">

        @if (session('status') === 'password-updated')
            <flux:callout class="mb-4" variant="success" icon="check-circle">
                <flux:callout.text>{{ __('Password updated.') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('security.password.update') }}" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <flux:input
                name="current_password"
                :label="__('Current password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            @error('current_password')
                <flux:error>{{ $message }}</flux:error>
            @enderror

            <flux:input
                name="password"
                :label="__('New password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            @error('password')
                <flux:error>{{ $message }}</flux:error>
            @enderror

            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-password-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12" x-data="twoFactorSection({{ json_encode($twoFactorEnabled) }}, {{ json_encode($requiresConfirmation) }})">
                <flux:heading>{{ __('Two-factor authentication') }}</flux:heading>
                <flux:subheading>{{ __('Manage your two-factor authentication settings') }}</flux:subheading>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm mt-4">
                    <template x-if="enabled">
                        <div class="space-y-4">
                            <flux:text>
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </flux:text>

                            <template x-if="showQr">
                                <div class="space-y-4">
                                    <flux:text>{{ __('Scan the following QR code with your authenticator application.') }}</flux:text>
                                    <div x-html="qrCode" class="p-4 bg-white inline-block rounded-lg"></div>

                                    <template x-if="requiresConfirmation">
                                        <div class="space-y-2">
                                            <flux:input
                                                x-model="confirmCode"
                                                :label="__('Confirmation code')"
                                                type="text"
                                                inputmode="numeric"
                                            />
                                            <flux:button @click="confirmTwoFactor" variant="primary">
                                                {{ __('Confirm') }}
                                            </flux:button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="showRecoveryCodes">
                                <div class="space-y-2">
                                    <flux:text class="font-medium">{{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.') }}</flux:text>
                                    <div class="grid gap-1 max-w-xl text-sm font-mono p-4 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                                        <template x-for="code in recoveryCodes" :key="code">
                                            <p x-text="code"></p>
                                        </template>
                                    </div>
                                    <flux:button @click="regenerateCodes" variant="outline">
                                        {{ __('Regenerate recovery codes') }}
                                    </flux:button>
                                </div>
                            </template>

                            <div class="flex justify-start gap-2">
                                <flux:button @click="loadRecoveryCodes" variant="outline">
                                    {{ __('Show recovery codes') }}
                                </flux:button>
                                <flux:button variant="danger" @click="disableTwoFactor">
                                    {{ __('Disable 2FA') }}
                                </flux:button>
                            </div>
                        </div>
                    </template>

                    <template x-if="!enabled">
                        <div class="space-y-4">
                            <flux:text variant="subtle">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </flux:text>
                            <flux:button variant="primary" @click="enableTwoFactor">
                                {{ __('Enable 2FA') }}
                            </flux:button>
                        </div>
                    </template>
                </div>
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="mt-12">
                <flux:heading>{{ __('Passkeys') }}</flux:heading>
                <flux:subheading>{{ __('Manage your passkeys for passwordless sign-in') }}</flux:subheading>

                <div class="mt-6 flex flex-col w-full mx-auto space-y-6 text-sm" x-data="passkeyManager()">
                    <div class="border rounded-lg border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <template x-if="passkeys.length > 0">
                            <div>
                                <template x-for="(passkey, idx) in passkeys" :key="passkey.id">
                                    <div
                                        class="flex items-center justify-between p-4"
                                        :class="idx < passkeys.length - 1 ? 'border-b border-zinc-200 dark:border-zinc-700' : ''"
                                    >
                                        <div class="flex items-center gap-4">
                                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                                <flux:icon.key class="size-5 text-zinc-500 dark:text-zinc-400" />
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2.5">
                                                    <p class="font-medium tracking-tight" x-text="passkey.name"></p>
                                                    <template x-if="passkey.authenticator">
                                                        <flux:badge size="sm" x-text="passkey.authenticator"></flux:badge>
                                                    </template>
                                                </div>
                                                <p class="text-zinc-500 dark:text-zinc-400 text-xs">
                                                    <span x-text="'{{ __('Added') }} ' + passkey.created_at_diff"></span>
                                                    <template x-if="passkey.last_used_at_diff">
                                                        <span>
                                                            <span class="opacity-50 mx-1">/</span>
                                                            <span x-text="'{{ __('Last used') }} ' + passkey.last_used_at_diff"></span>
                                                        </span>
                                                    </template>
                                                </p>
                                            </div>
                                        </div>
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            icon:variant="outline"
                                            class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                                            @click="confirmDelete(passkey)"
                                        />
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="passkeys.length === 0">
                            <div class="p-8 text-center">
                                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon.key class="size-7 text-zinc-400 dark:text-zinc-500" />
                                </div>
                                <p class="font-medium">{{ __('No passkeys yet') }}</p>
                                <flux:text class="mt-1">{{ __('Add a passkey to sign in without a password') }}</flux:text>
                            </div>
                        </template>
                    </div>

                    <x-passkey-registration />

                    <flux:modal name="delete-passkey-modal" class="max-w-md md:min-w-md" x-model="showDeleteModal">
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <flux:heading size="lg">{{ __('Remove passkey') }}</flux:heading>
                                <flux:text>
                                    {{ __('Are you sure you want to remove the passkey ":name"?', ['name' => '']) }}
                                    <span x-text="deletingPasskeyName"></span>
                                    {{ __('You will no longer be able to use it to sign in.') }}
                                </flux:text>
                            </div>
                            <div class="flex gap-3 justify-end">
                                <flux:button variant="outline" @click="showDeleteModal = false">
                                    {{ __('Cancel') }}
                                </flux:button>
                                <flux:button variant="danger" @click="deletePasskey">
                                    {{ __('Remove passkey') }}
                                </flux:button>
                            </div>
                        </div>
                    </flux:modal>
                </div>
            </section>
        @endif

    </x-pages::settings.layout>
</section>

@push('scripts')
<script>
function twoFactorSection(initialEnabled, requiresConfirmation) {
    return {
        enabled: initialEnabled,
        requiresConfirmation,
        showQr: false,
        showRecoveryCodes: false,
        qrCode: '',
        recoveryCodes: [],
        confirmCode: '',

        async enableTwoFactor() {
            await fetch('/user/two-factor-authentication', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            this.enabled = true;
            this.showQr = true;
            const qrRes = await fetch('/user/two-factor-qr-code', {
                headers: { Accept: 'application/json' },
            });
            const data = await qrRes.json();
            this.qrCode = data.svg;
        },

        async confirmTwoFactor() {
            const res = await fetch('/user/confirmed-two-factor-authentication', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ code: this.confirmCode }),
            });
            if (res.ok) {
                this.showQr = false;
                this.confirmCode = '';
            }
        },

        async disableTwoFactor() {
            await fetch('/user/two-factor-authentication', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            this.enabled = false;
            this.showQr = false;
            this.showRecoveryCodes = false;
        },

        async loadRecoveryCodes() {
            const res = await fetch('/user/two-factor-recovery-codes', {
                headers: { Accept: 'application/json' },
            });
            this.recoveryCodes = await res.json();
            this.showRecoveryCodes = true;
        },

        async regenerateCodes() {
            await fetch('/user/two-factor-recovery-codes', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            await this.loadRecoveryCodes();
        },
    };
}

function passkeyManager() {
    return {
        passkeys: [],
        showDeleteModal: false,
        deletingPasskeyId: null,
        deletingPasskeyName: '',

        async init() {
            await this.loadPasskeys();
        },

        async loadPasskeys() {
            const res = await fetch('/passkeys', { headers: { Accept: 'application/json' } });
            if (res.ok) {
                this.passkeys = await res.json();
            }
        },

        confirmDelete(passkey) {
            this.deletingPasskeyId = passkey.id;
            this.deletingPasskeyName = passkey.name;
            this.showDeleteModal = true;
        },

        async deletePasskey() {
            if (!this.deletingPasskeyId) return;
            await fetch(`/passkeys/${this.deletingPasskeyId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            this.showDeleteModal = false;
            this.deletingPasskeyId = null;
            this.deletingPasskeyName = '';
            await this.loadPasskeys();
        },
    };
}
</script>
@endpush
