<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        @if (session('status') === 'profile-updated')
            <flux:callout class="mb-4" variant="success" icon="check-circle">
                <flux:callout.text>{{ __('Profile updated.') }}</flux:callout.text>
            </flux:callout>
        @endif

        @if (session('status') === 'verification-link-sent')
            <flux:callout class="mb-4" variant="success" icon="check-circle">
                <flux:callout.text>{{ __('A new verification link has been sent to your email address.') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="my-6 w-full space-y-6">
            @csrf
            @method('PUT')

            <flux:input
                name="name"
                :label="__('Name')"
                type="text"
                :value="old('name', auth()->user()->name)"
                required
                autofocus
                autocomplete="name"
            />
            @error('name')
                <flux:error>{{ $message }}</flux:error>
            @enderror

            <div>
                <flux:input
                    name="email"
                    :label="__('Email')"
                    type="email"
                    :value="old('email', auth()->user()->email)"
                    required
                    autocomplete="email"
                />
                @error('email')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>

        <section class="mt-10 space-y-6">
            <div class="relative mb-5">
                <flux:heading>{{ __('Delete account') }}</flux:heading>
                <flux:subheading>{{ __('Delete your account and all of its resources') }}</flux:subheading>
            </div>

            <flux:modal.trigger name="confirm-user-deletion">
                <flux:button variant="danger" data-test="delete-user-button">
                    {{ __('Delete account') }}
                </flux:button>
            </flux:modal.trigger>

            <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
                <form method="POST" action="{{ route('settings.user.destroy') }}" class="space-y-6">
                    @csrf
                    @method('DELETE')

                    <div>
                        <flux:heading size="lg">{{ __('Are you sure you want to delete your account?') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </flux:subheading>
                    </div>

                    <flux:input name="password" :label="__('Password')" type="password" viewable />
                    @error('password')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror

                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                        <flux:modal.close>
                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>

                        <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                            {{ __('Delete account') }}
                        </flux:button>
                    </div>
                </form>
            </flux:modal>
        </section>
    </x-pages::settings.layout>
</section>
