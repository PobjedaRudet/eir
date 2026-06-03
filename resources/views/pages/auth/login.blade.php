<x-layouts::auth :title="__('Log in')">
    <div class="rounded-3xl border border-white/8 bg-white/4 p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-sm sm:p-8">
        <div class="flex flex-col gap-6">
            <x-auth-header :title="__('Prijava')" :description="__('Unesite email adresu i lozinku za pristup aplikaciji')" />

            <!-- Session Status -->
            <x-auth-session-status class="text-center text-zinc-300" :status="session('status')" />

            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
                @csrf

                <!-- Email Address -->
                <flux:input
                    name="email"
                    :label="__('Email adresa')"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                />

                <!-- Password -->
                <div class="relative">
                    <flux:input
                        name="password"
                        :label="__('Lozinka')"
                        type="password"
                        required
                        autocomplete="current-password"
                        :placeholder="__('Lozinka')"
                        viewable
                    />

                    @if (Route::has('password.request'))
                        <flux:link class="absolute top-0 text-sm end-0 text-zinc-300 hover:text-white" :href="route('password.request')" wire:navigate>
                            {{ __('Zaboravili ste lozinku?') }}
                        </flux:link>
                    @endif
                </div>

                <!-- Remember Me -->
                <flux:checkbox name="remember" :label="__('Zapamti me')" :checked="old('remember')" />

                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full bg-blue-600 hover:bg-blue-500" data-test="login-button">
                        {{ __('Prijava') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
