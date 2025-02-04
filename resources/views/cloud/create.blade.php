<x-guest-layout>
    <p class="mt-1 text-sm/6 text-gray-600">
        {{ __('In order to continue, you need to configure your Nextcloud account. This will create a folder called "Solid" that will be used to store your POD data.') }}
    </p>

    <form method="POST" action="{{ route('cloud.store') }}">
        @csrf

        <div class="mt-4">
            <x-input-label for="nextcloud_url" :value="__('Nextcloud URL')" />
            <x-text-input id="nextcloud_url" name="nextcloud_url" type="text" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_url')" />
        </div>

        <div class="mt-4">
            <x-input-label for="nextcloud_username" :value="__('Nextcloud Username')" />
            <x-text-input id="nextcloud_username" name="nextcloud_username" type="text" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_username')" />
        </div>

        <div class="mt-4">
            <x-input-label for="nextcloud_password" :value="__('Nextcloud Password')" />
            <p class="mt-1 text-xs/6 text-gray-600">
                {{ __('If you don\'t want to use your real password, you can go to "Settings > Security > Devices & session" and create an App Password.') }}
            </p>
            <x-text-input id="nextcloud_password" name="nextcloud_password" type="password" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_password')" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Setup Cloud') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
