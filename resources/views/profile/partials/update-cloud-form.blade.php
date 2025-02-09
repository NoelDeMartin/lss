<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Nextcloud account') }}
        </h2>

        @if (session('status') === 'cloud-sync-failed')
            <p class="mt-1 text-sm text-red-600">
                {{ __('It wasn\'t possible to connect to Nextcloud, are you sure the credentials were correct?') }}
            </p>
        @else
            <p class="mt-1 text-sm text-gray-600">
                {{ __('Configure your Nextcloud account. This will create a folder called "Solid" that will be used to store your POD data.') }}
            </p>
        @endif
    </header>

    <form method="post" action="{{ route('cloud.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="nextcloud_url" :value="__('Nextcloud URL')" />
            <x-text-input id="nextcloud_url" name="nextcloud_url" type="text" class="mt-1 block w-full" :value="old('nextcloud_url', $user->nextcloud_url)" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_url')" />
        </div>

        <div>
            <x-input-label for="nextcloud_username" :value="__('Nextcloud Username')" />
            <x-text-input id="nextcloud_username" name="nextcloud_username" type="text" class="mt-1 block w-full" :value="old('nextcloud_username', $user->nextcloud_username)" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_username')" />
        </div>

        <div>
            <x-input-label for="nextcloud_password" :value="__('Nextcloud Password')" />
            <p class="mt-1 text-xs/6 text-gray-600">
                {{ __('If you don\'t want to use your real password, you can go to "Settings > Security > Devices & session" and create an App Password.') }}
            </p>
            <x-text-input id="nextcloud_password" name="nextcloud_password" type="password" class="mt-1 block w-full" :value="old('nextcloud_password', $user->nextcloud_password)" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_password')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'cloud-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
